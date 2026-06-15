<?php

namespace App\Services\Search;

use App\Services\Search\Index\GlobalSearchIndexer;
use RuntimeException;

class GlobalSearchService
{
    public function __construct(
        private readonly TypesenseService $typesense,
        private readonly GlobalSearchIndexer $indexer,
        private readonly DatabaseGlobalSearchService $databaseSearch,
    ) {}

    public function search(string $query, array $options = []): array
    {
        $minLength = (int) config('global_search.search.min_query_length', 2);
        $trimmedQuery = trim($query);

        if (mb_strlen($trimmedQuery) < $minLength) {
            throw new RuntimeException('Search query must be at least ' . $minLength . ' characters.');
        }

        if (!$this->shouldUseTypesense()) {
            if (!config('global_search.fallback_to_database', true)) {
                throw new RuntimeException('Typesense is not reachable. Check TYPESENSE_HOST in .env');
            }

            return $this->databaseSearch->search($trimmedQuery, $options);
        }

        $limit = min(
            (int) ($options['limit'] ?? config('global_search.search.default_limit', 20)),
            (int) config('global_search.search.max_limit', 100)
        );
        $page = max(1, (int) ($options['page'] ?? 1));
        $types = $this->normalizeTypes($options['types'] ?? []);

        $response = $this->typesense->search($trimmedQuery, [
            'limit' => $limit,
            'page' => $page,
            'types' => $types,
        ]);

        $hits = $response['hits'] ?? [];
        $total = (int) ($response['found'] ?? count($hits));
        $maxRawScore = max(array_map(
            fn (array $hit) => $this->typesense->rawRelevanceScore($hit),
            $hits
        ) ?: [0]);

        $results = array_map(function (array $hit) use ($maxRawScore) {
            $document = $hit['document'] ?? [];

            return [
                'entity_type' => $document['entity_type'] ?? null,
                'entity_type_label' => config('global_search.entity_types.' . ($document['entity_type'] ?? '') . '.label'),
                'entity_id' => $document['entity_id'] ?? null,
                'title' => $document['title'] ?? null,
                'subtitle' => $document['subtitle'] ?? null,
                'route' => $document['route'] ?? null,
                'metadata' => $document['metadata'] ?? [],
                'score' => $this->typesense->normalizeScore(
                    $this->typesense->rawRelevanceScore($hit),
                    $maxRawScore
                ),
                'highlight' => $this->typesense->mapHighlights($hit),
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
            'search_engine' => 'typesense',
            'results' => $results,
        ];
    }

    public function reindex(bool $fresh = false): array
    {
        return $this->indexer->reindexAll($fresh);
    }

    public function status(): array
    {
        $connected = $this->typesense->ping();
        $useTypesense = $this->shouldUseTypesense();

        return [
            'enabled' => $this->typesense->isEnabled(),
            'connected' => $connected,
            'index' => $this->typesense->collectionName(),
            'index_exists' => $this->typesense->collectionExists(),
            'fallback_to_database' => (bool) config('global_search.fallback_to_database', true),
            'search_engine' => $useTypesense ? 'typesense' : 'database',
            'entity_types' => $this->availableTypes(),
        ];
    }

    private function shouldUseTypesense(): bool
    {
        return $this->typesense->isEnabled()
            && $this->typesense->ping()
            && $this->typesense->collectionExists();
    }

    private function availableTypes(): array
    {
        return collect(config('global_search.entity_types', []))
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
