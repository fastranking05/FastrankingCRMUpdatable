<?php

namespace App\Services\Search;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;
use Typesense\Exceptions\TypesenseClientError;

class TypesenseService
{
    private ?Client $client = null;

    public function isEnabled(): bool
    {
        return (bool) config('global_search.enabled', true);
    }

    public function client(): Client
    {
        if ($this->client !== null) {
            return $this->client;
        }

        $this->client = new Client(config('scout.typesense.client-settings'));

        return $this->client;
    }

    public function collectionName(): string
    {
        return (string) config('global_search.collection', 'fastranking_global_search');
    }

    public function ping(): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $this->client()->health->retrieve();

            return true;
        } catch (\Throwable $e) {
            Log::warning('Typesense ping failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function collectionExists(): bool
    {
        try {
            $this->client()->collections[$this->collectionName()]->retrieve();

            return true;
        } catch (ObjectNotFound) {
            return false;
        } catch (\Throwable $e) {
            Log::warning('Typesense collection exists check failed', ['error' => $e->getMessage()]);

            return false;
        }
    }

    public function deleteCollection(): void
    {
        if (!$this->collectionExists()) {
            return;
        }

        try {
            $this->client()->collections[$this->collectionName()]->delete();
        } catch (ObjectNotFound) {
            // Collection already removed.
        }
    }

    public function search(string $query, array $options = []): array
    {
        if (!$this->collectionExists()) {
            throw new RuntimeException('Search index has not been created. Run php artisan search:reindex first.');
        }

        $params = [
            'q' => $query,
            'query_by' => 'title,subtitle,search_text,entity_id',
            'query_by_weights' => '4,2,1,3',
            'highlight_full_fields' => 'title,subtitle,search_text',
            'highlight_affix_num_tokens' => 4,
            'per_page' => $options['limit'] ?? 20,
            'page' => $options['page'] ?? 1,
            'sort_by' => '_text_match:desc,updated_at:desc',
        ];

        if (!empty($options['types'])) {
            $types = array_map(
                fn (string $type) => '`' . str_replace('`', '', $type) . '`',
                $options['types']
            );
            $params['filter_by'] = 'entity_type:[' . implode(',', $types) . ']';
        }

        try {
            return $this->client()->collections[$this->collectionName()]->documents->search($params);
        } catch (TypesenseClientError $e) {
            throw new RuntimeException('Typesense search failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function mapHighlights(array $hit): array
    {
        $highlights = [];

        foreach ($hit['highlights'] ?? [] as $highlight) {
            $field = $highlight['field'] ?? null;
            $snippet = $highlight['snippet'] ?? ($highlight['snippets'][0] ?? null);

            if ($field && $snippet) {
                $highlights[$field][] = $snippet;
            }
        }

        return $highlights;
    }

    public function rawRelevanceScore(array $hit): float
    {
        $info = $hit['text_match_info'] ?? [];
        $bestFieldScore = (float) ($info['best_field_score'] ?? $hit['text_match'] ?? 0);
        $fieldsMatched = max(1, (int) ($info['fields_matched'] ?? 1));
        $tokensMatched = max(1, (int) ($info['tokens_matched'] ?? 1));
        $weight = max(1, (int) ($info['best_field_weight'] ?? 1));

        return $bestFieldScore * $fieldsMatched * $tokensMatched * $weight;
    }

    public function normalizeScore(float $raw, float $maxRaw): ?float
    {
        if ($raw <= 0 || $maxRaw <= 0) {
            return null;
        }

        return round(($raw / $maxRaw) * 100, 2);
    }
}
