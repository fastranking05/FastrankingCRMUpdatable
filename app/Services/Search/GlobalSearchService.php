<?php

namespace App\Services\Search;

use App\Services\Permission\DepartmentModulePermissionService;
use App\Services\Search\Index\GlobalSearchIndexer;
use App\Support\LeadDisplayId;
use RuntimeException;

class GlobalSearchService
{
    public function __construct(
        private readonly TypesenseService $typesense,
        private readonly GlobalSearchIndexer $indexer,
        private readonly DatabaseGlobalSearchService $databaseSearch,
        private readonly DepartmentModulePermissionService $permissions,
    ) {}

    public function search(string $query, array $options = []): array
    {
        $minLength = (int) config('global_search.search.min_query_length', 2);
        $trimmedQuery = trim($query);

        if (mb_strlen($trimmedQuery) < $minLength) {
            throw new RuntimeException('Search query must be at least ' . $minLength . ' characters.');
        }

        $limit = min(
            (int) ($options['limit'] ?? config('global_search.search.default_limit', 20)),
            (int) config('global_search.search.max_limit', 100)
        );
        $page = max(1, (int) ($options['page'] ?? 1));
        $userId = isset($options['user_id']) ? (int) $options['user_id'] : null;
        $types = $this->resolveAllowedTypes($userId, $this->normalizeTypes($options['types'] ?? []));

        if ($userId !== null && $types === []) {
            return $this->emptySearchResponse($trimmedQuery, $page, $limit);
        }

        if (!$this->shouldUseTypesense()) {
            if (!config('global_search.fallback_to_database', true)) {
                throw new RuntimeException('Typesense is not reachable. Check TYPESENSE_HOST in .env');
            }

            return $this->databaseSearch->search($trimmedQuery, array_merge($options, [
                'types' => $types,
            ]));
        }

        $response = $this->typesense->search($this->expandLeadIdSearchQuery($trimmedQuery), [
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
            'available_types' => $this->availableTypesForUser($userId),
            'search_engine' => 'typesense',
            'results' => $results,
        ];
    }

    public function reindex(bool $fresh = false): array
    {
        return $this->indexer->reindexAll($fresh);
    }

    public function status(?int $userId = null): array
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
            'entity_types' => $this->availableTypes($userId),
        ];
    }

    private function shouldUseTypesense(): bool
    {
        return $this->typesense->isEnabled()
            && $this->typesense->ping()
            && $this->typesense->collectionExists();
    }

    private function availableTypes(?int $userId = null): array
    {
        $keys = $userId === null
            ? array_keys(config('global_search.entity_types', []))
            : $this->permissions->allowedSearchEntityTypesForUser($userId);

        return collect(config('global_search.entity_types', []))
            ->only($keys)
            ->map(fn (array $config, string $key) => [
                'key' => $key,
                'label' => $config['label'] ?? $key,
            ])
            ->values()
            ->all();
    }

    private function availableTypesForUser(?int $userId): array
    {
        return $this->availableTypes($userId);
    }

    /**
     * @param  array<int, string>  $requestedTypes
     * @return array<int, string>
     */
    private function resolveAllowedTypes(?int $userId, array $requestedTypes): array
    {
        if ($userId === null) {
            return $requestedTypes;
        }

        return $this->permissions->allowedSearchEntityTypesForUser($userId, $requestedTypes);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptySearchResponse(string $query, int $page, int $limit): array
    {
        return [
            'query' => $query,
            'total' => 0,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => 0,
            'counts_by_type' => [],
            'available_types' => [],
            'search_engine' => config('global_search.fallback_to_database', true) ? 'database' : 'typesense',
            'results' => [],
        ];
    }

    private function normalizeTypes(array $types): array
    {
        $allowed = SearchEntityType::all();

        return array_values(array_filter(
            array_map('strval', $types),
            fn (string $type) => in_array($type, $allowed, true)
        ));
    }

    private function expandLeadIdSearchQuery(string $query): string
    {
        $leadId = LeadDisplayId::resolveNumericId($query);

        if ($leadId === null) {
            return $query;
        }

        $parts = array_unique(array_filter([
            $query,
            (string) $leadId,
            LeadDisplayId::format($leadId),
        ]));

        return implode(' ', $parts);
    }
}
