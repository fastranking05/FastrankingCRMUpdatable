<?php

namespace App\Services\Search;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ElasticsearchService
{
    private ?Client $client = null;

    public function isEnabled(): bool
    {
        return (bool) config('elasticsearch.enabled', true);
    }

    public function client(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $builder = ClientBuilder::create()
            ->setHosts(config('elasticsearch.hosts', ['http://127.0.0.1:9200']))
            ->setRetries(2);

        $username = config('elasticsearch.username');
        $password = config('elasticsearch.password');

        if ($username && $password) {
            $builder->setBasicAuthentication($username, $password);
        }

        $this->client = $builder->build();

        return $this->client;
    }

    public function indexName(): string
    {
        return (string) config('elasticsearch.index', 'fastranking_global_search');
    }

    public function ping(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $response = $this->client()->ping();

            return (bool) $response->asBool();
        } catch (\Throwable $e) {
            Log::warning('Elasticsearch ping failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function indexExists(): bool
    {
        try {
            $response = $this->client()->indices()->exists(['index' => $this->indexName()]);

            return (bool) $response->asBool();
        } catch (\Throwable $e) {
            Log::warning('Elasticsearch index exists check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function ensureIndexExists(): void
    {
        if ($this->indexExists()) {
            return;
        }

        $this->client()->indices()->create([
            'index' => $this->indexName(),
            'body' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                    'analysis' => [
                        'analyzer' => [
                            'global_search_analyzer' => [
                                'type' => 'custom',
                                'tokenizer' => 'standard',
                                'filter' => ['lowercase', 'asciifolding'],
                            ],
                        ],
                    ],
                ],
                'mappings' => [
                    'properties' => [
                        'entity_type' => ['type' => 'keyword'],
                        'entity_id' => ['type' => 'keyword'],
                        'title' => [
                            'type' => 'text',
                            'analyzer' => 'global_search_analyzer',
                            'fields' => [
                                'keyword' => ['type' => 'keyword'],
                            ],
                        ],
                        'subtitle' => [
                            'type' => 'text',
                            'analyzer' => 'global_search_analyzer',
                        ],
                        'search_text' => [
                            'type' => 'text',
                            'analyzer' => 'global_search_analyzer',
                        ],
                        'route' => ['type' => 'keyword'],
                        'metadata' => ['type' => 'object', 'enabled' => true],
                        'created_at' => ['type' => 'date'],
                        'updated_at' => ['type' => 'date'],
                    ],
                ],
            ],
        ]);
    }

    public function indexDocument(string $documentId, array $document): void
    {
        $this->ensureIndexExists();

        $this->client()->index([
            'index' => $this->indexName(),
            'id' => $documentId,
            'body' => $document,
            'refresh' => false,
        ]);
    }

    public function bulkIndex(array $documents): int
    {
        if ($documents === []) {
            return 0;
        }

        $this->ensureIndexExists();

        $body = [];
        foreach ($documents as $documentId => $document) {
            $body[] = ['index' => ['_index' => $this->indexName(), '_id' => $documentId]];
            $body[] = $document;
        }

        $response = $this->client()->bulk([
            'body' => $body,
            'refresh' => false,
        ]);

        $result = $response->asArray();
        $indexed = 0;

        if (!empty($result['items'])) {
            foreach ($result['items'] as $item) {
                $action = $item['index'] ?? $item['create'] ?? null;
                if ($action && empty($action['error'])) {
                    $indexed++;
                }
            }
        }

        return $indexed;
    }

    public function deleteDocument(string $documentId): void
    {
        if (!$this->indexExists()) {
            return;
        }

        try {
            $this->client()->delete([
                'index' => $this->indexName(),
                'id' => $documentId,
            ]);
        } catch (\Throwable $e) {
            if (!str_contains($e->getMessage(), '404')) {
                throw $e;
            }
        }
    }

    public function search(array $body): array
    {
        if (!$this->indexExists()) {
            throw new RuntimeException('Search index has not been created. Run php artisan search:reindex first.');
        }

        $response = $this->client()->search([
            'index' => $this->indexName(),
            'body' => $body,
        ]);

        return $response->asArray();
    }

    public function refreshIndex(): void
    {
        if ($this->indexExists()) {
            $this->client()->indices()->refresh(['index' => $this->indexName()]);
        }
    }

    public function deleteIndex(): void
    {
        if ($this->indexExists()) {
            $this->client()->indices()->delete(['index' => $this->indexName()]);
        }
    }
}
