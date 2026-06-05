<?php

namespace App\Services\Search;

use App\Services\Search\Index\GlobalSearchIndexer;
use RuntimeException;

class GlobalSearchService
{
    public function __construct(
        private readonly ElasticsearchService $elasticsearch,
        private readonly GlobalSearchIndexer $indexer,
        private readonly DatabaseGlobalSearchService $databaseSearch,
    ) {}

    public function search(string $query, array $options = []): array
    {
        $minLength = (int) config('elasticsearch.search.min_query_length', 2);
        $trimmedQuery = trim($query);

        if (mb_strlen($trimmedQuery) < $minLength) {
            throw new RuntimeException('Search query must be at least ' . $minLength . ' characters.');
        }

        if (!$this->shouldUseElasticsearch()) {
            if (!config('elasticsearch.fallback_to_database', true)) {
                throw new RuntimeException('Elasticsearch is not reachable. Check ELASTICSEARCH_HOST in .env');
            }

            return $this->databaseSearch->search($trimmedQuery, $options);
        }

        $limit = min(
            (int) ($options['limit'] ?? config('elasticsearch.search.default_limit', 20)),
            (int) config('elasticsearch.search.max_limit', 100)
        );
        $page = max(1, (int) ($options['page'] ?? 1));
        $from = ($page - 1) * $limit;
        $types = $this->normalizeTypes($options['types'] ?? []);

        $must = [
            [
                'multi_match' => [
                    'query' => $trimmedQuery,
                    'fields' => [
                        'title^4',
                        'subtitle^2',
                        'search_text',
                        'entity_id^3',
                    ],
                    'type' => 'best_fields',
                    'fuzziness' => 'AUTO',
                    'operator' => 'or',
                ],
            ],
        ];

        $should = [
            [
                'multi_match' => [
                    'query' => $trimmedQuery,
                    'fields' => ['title', 'subtitle', 'search_text', 'entity_id'],
                    'type' => 'phrase_prefix',
                ],
            ],
        ];

        $filter = [];
        if ($types !== []) {
            $filter[] = ['terms' => ['entity_type' => $types]];
        }

        $body = [
            'from' => $from,
            'size' => $limit,
            'track_total_hits' => true,
            'query' => [
                'bool' => [
                    'must' => $must,
                    'should' => $should,
                    'filter' => $filter,
                    'minimum_should_match' => 0,
                ],
            ],
            'highlight' => [
                'pre_tags' => ['<mark>'],
                'post_tags' => ['</mark>'],
                'fields' => [
                    'title' => new \stdClass(),
                    'subtitle' => new \stdClass(),
                    'search_text' => ['fragment_size' => 150, 'number_of_fragments' => 1],
                ],
            ],
            'sort' => [
                ['_score' => ['order' => 'desc']],
                ['updated_at' => ['order' => 'desc']],
            ],
        ];

        $response = $this->elasticsearch->search($body);
        $hits = $response['hits']['hits'] ?? [];
        $total = $response['hits']['total']['value'] ?? count($hits);

        $results = array_map(function (array $hit) {
            $source = $hit['_source'] ?? [];

            return [
                'entity_type' => $source['entity_type'] ?? null,
                'entity_type_label' => config('elasticsearch.entity_types.' . ($source['entity_type'] ?? '') . '.label'),
                'entity_id' => $source['entity_id'] ?? null,
                'title' => $source['title'] ?? null,
                'subtitle' => $source['subtitle'] ?? null,
                'route' => $source['route'] ?? null,
                'metadata' => $source['metadata'] ?? [],
                'score' => $hit['_score'] ?? null,
                'highlight' => $hit['highlight'] ?? [],
            ];
        }, $hits);

        $grouped = [];
        foreach ($results as $result) {
            $type = $result['entity_type'] ?? 'unknown';
            $grouped[$type] = ($grouped[$type] ?? 0) + 1;
        }

        return [
            'query' => $trimmedQuery,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
            'counts_by_type' => $grouped,
            'available_types' => $this->availableTypes(),
            'search_engine' => 'elasticsearch',
            'results' => $results,
        ];
    }

    public function reindex(bool $fresh = false): array
    {
        return $this->indexer->reindexAll($fresh);
    }

    public function status(): array
    {
        $connected = $this->elasticsearch->ping();
        $useElasticsearch = $this->shouldUseElasticsearch();

        return [
            'enabled' => $this->elasticsearch->isEnabled(),
            'connected' => $connected,
            'index' => $this->elasticsearch->indexName(),
            'index_exists' => $this->elasticsearch->indexExists(),
            'fallback_to_database' => (bool) config('elasticsearch.fallback_to_database', true),
            'search_engine' => $useElasticsearch ? 'elasticsearch' : 'database',
            'entity_types' => $this->availableTypes(),
        ];
    }

    private function shouldUseElasticsearch(): bool
    {
        return $this->elasticsearch->isEnabled()
            && $this->elasticsearch->ping()
            && $this->elasticsearch->indexExists();
    }

    private function availableTypes(): array
    {
        return collect(config('elasticsearch.entity_types', []))
            ->map(fn (array $config, string $key) => [
                'key' => $key,
                'label' => $config['label'] ?? $key,
            ])
            ->values()
            ->all();
    }

    private function normalizeTypes(array $types): array
    {
        $allowed = SearchEntityType::all();

        return array_values(array_filter(
            array_map('strval', $types),
            fn (string $type) => in_array($type, $allowed, true)
        ));
    }
}
