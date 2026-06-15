<?php

namespace App\Services\Search\Index;

use App\Models\Appointment;
use App\Models\Comment;
use App\Models\Consultation;
use App\Models\Deal;
use App\Models\Email;
use App\Models\FollowupAuthPerson;
use App\Models\FollowupBusiness;
use App\Models\GlobalSearchDocument;
use App\Models\SeoDetail;
use App\Models\User;
use App\Services\Search\TypesenseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class GlobalSearchIndexer
{
    public function __construct(
        private readonly TypesenseService $typesense,
        private readonly GlobalSearchDocumentBuilder $documentBuilder,
    ) {}

    public function indexModel(Model $model): void
    {
        if (!$this->typesense->isEnabled()) {
            return;
        }

        $documentId = $this->documentBuilder->documentIdForModel($model);
        $document = $this->documentBuilder->buildFromModel($model);

        if ($documentId === null || $document === null) {
            return;
        }

        try {
            GlobalSearchDocument::upsertFromBuiltDocument($documentId, $document)->searchable();
        } catch (\Throwable $e) {
            Log::warning('Failed to index search document', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function deleteModel(Model $model): void
    {
        if (!$this->typesense->isEnabled()) {
            return;
        }

        $documentId = $this->documentBuilder->documentIdForModel($model);

        if ($documentId === null) {
            return;
        }

        try {
            $record = GlobalSearchDocument::find($documentId);

            if ($record !== null) {
                $record->unsearchable();
                $record->delete();
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to delete search document', [
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function reindexAll(bool $fresh = false): array
    {
        if (!$this->typesense->isEnabled()) {
            throw new \RuntimeException('Global search is disabled.');
        }

        if (!$this->typesense->ping()) {
            throw new \RuntimeException('Typesense is not reachable. Check TYPESENSE_HOST in .env');
        }

        if ($fresh) {
            GlobalSearchDocument::removeAllFromSearch();
            $this->typesense->deleteCollection();
            GlobalSearchDocument::query()->delete();
        }

        $counts = [];
        $batchSize = 500;

        GlobalSearchDocument::withoutSyncingToSearch(function () use ($batchSize, &$counts) {
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
        });

        GlobalSearchDocument::makeAllSearchable();

        $counts['total'] = array_sum($counts);

        return $counts;
    }

    private function bulkIndexQuery($query, int $batchSize): int
    {
        $indexed = 0;

        $query->chunkById($batchSize, function ($models) use (&$indexed) {
            foreach ($models as $model) {
                $documentId = $this->documentBuilder->documentIdForModel($model);
                $document = $this->documentBuilder->buildFromModel($model);

                if ($documentId !== null && $document !== null) {
                    GlobalSearchDocument::upsertFromBuiltDocument($documentId, $document);
                    $indexed++;
                }
            }
        });

        return $indexed;
    }
}
