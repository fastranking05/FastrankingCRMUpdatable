<?php

namespace App\Services\Search\Index;

use App\Models\Appointment;
use App\Models\Comment;
use App\Models\Consultation;
use App\Models\Deal;
use App\Models\Email;
use App\Models\FollowupAuthPerson;
use App\Models\FollowupBusiness;
use App\Models\SeoDetail;
use App\Models\User;
use App\Services\Search\ElasticsearchService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class GlobalSearchIndexer
{
    public function __construct(
        private readonly ElasticsearchService $elasticsearch,
        private readonly GlobalSearchDocumentBuilder $documentBuilder,
    ) {}

    public function indexModel(Model $model): void
    {
        if (!$this->elasticsearch->isEnabled()) {
            return;
        }

        $documentId = $this->documentBuilder->documentIdForModel($model);
        $document = $this->documentBuilder->buildFromModel($model);

        if ($documentId === null || $document === null) {
            return;
        }

        try {
            $this->elasticsearch->indexDocument($documentId, $document);
        } catch (\Throwable $e) {
            Log::warning('Failed to index search document', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleteModel(Model $model): void
    {
        if (!$this->elasticsearch->isEnabled()) {
            return;
        }

        $documentId = $this->documentBuilder->documentIdForModel($model);

        if ($documentId === null) {
            return;
        }

        try {
            $this->elasticsearch->deleteDocument($documentId);
        } catch (\Throwable $e) {
            Log::warning('Failed to delete search document', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function reindexAll(bool $fresh = false): array
    {
        if (!$this->elasticsearch->isEnabled()) {
            throw new \RuntimeException('Elasticsearch is disabled.');
        }

        if (!$this->elasticsearch->ping()) {
            throw new \RuntimeException('Elasticsearch is not reachable. Check ELASTICSEARCH_HOST in .env');
        }

        if ($fresh) {
            $this->elasticsearch->deleteIndex();
        }

        $this->elasticsearch->ensureIndexExists();

        $counts = [];
        $batchSize = 500;

        $counts['business'] = $this->bulkIndexQuery(
            FollowupBusiness::query()->orderBy('id'),
            $batchSize
        );
        $counts['contact'] = $this->bulkIndexQuery(
            FollowupAuthPerson::query()->orderBy('id'),
            $batchSize
        );
        $counts['deal'] = $this->bulkIndexQuery(
            Deal::query()->with('followupBusiness')->orderBy('id'),
            $batchSize
        );
        $counts['appointment'] = $this->bulkIndexQuery(
            Appointment::query()->with('followupBusiness')->orderBy('id'),
            $batchSize
        );
        $counts['user'] = $this->bulkIndexQuery(
            User::query()->orderBy('id'),
            $batchSize
        );
        $counts['email'] = $this->bulkIndexQuery(
            Email::query()->with('followupBusiness')->orderBy('id'),
            $batchSize
        );
        $counts['consultation'] = $this->bulkIndexQuery(
            Consultation::query()->orderBy('id'),
            $batchSize
        );
        $counts['seo_audit'] = $this->bulkIndexQuery(
            SeoDetail::query()->with('followupBusiness')->orderBy('id'),
            $batchSize
        );
        $counts['comment'] = $this->bulkIndexQuery(
            Comment::query()->with('followupBusiness')->orderBy('id'),
            $batchSize
        );

        $this->elasticsearch->refreshIndex();

        $counts['total'] = array_sum($counts);

        return $counts;
    }

    private function bulkIndexQuery($query, int $batchSize): int
    {
        $indexed = 0;

        $query->chunkById($batchSize, function ($models) use (&$indexed) {
            $documents = [];

            foreach ($models as $model) {
                $documentId = $this->documentBuilder->documentIdForModel($model);
                $document = $this->documentBuilder->buildFromModel($model);

                if ($documentId !== null && $document !== null) {
                    $documents[$documentId] = $document;
                }
            }

            if ($documents !== []) {
                $indexed += $this->elasticsearch->bulkIndex($documents);
            }
        });

        return $indexed;
    }
}
