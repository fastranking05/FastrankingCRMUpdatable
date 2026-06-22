<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class GlobalSearchDocument extends Model
{
    use Searchable;

    public const TITLE_MAX_LENGTH = 255;

    public const SUBTITLE_MAX_LENGTH = 255;

    public const ROUTE_MAX_LENGTH = 255;

    public const SEARCH_TEXT_MAX_LENGTH = 65000;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'entity_type',
        'entity_id',
        'title',
        'subtitle',
        'search_text',
        'route',
        'metadata',
        'source_created_at',
        'source_updated_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'source_created_at' => 'datetime',
        'source_updated_at' => 'datetime',
    ];

    public function getScoutKey(): mixed
    {
        return $this->id;
    }

    public function searchableAs(): string
    {
        return (string) config('global_search.collection', 'fastranking_global_search');
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'search_text' => $this->search_text,
            'route' => $this->route,
            'metadata' => $this->metadata ?? [],
            'created_at' => $this->source_created_at?->timestamp ?? $this->created_at?->timestamp ?? 0,
            'updated_at' => $this->source_updated_at?->timestamp ?? $this->updated_at?->timestamp ?? 0,
        ];
    }

    public static function upsertFromBuiltDocument(string $documentId, array $document): self
    {
        return static::updateOrCreate(
            ['id' => $documentId],
            [
                'entity_type' => $document['entity_type'],
                'entity_id' => (string) ($document['entity_id'] ?? ''),
                'title' => self::truncateString($document['title'] ?? '', self::TITLE_MAX_LENGTH),
                'subtitle' => self::truncateString($document['subtitle'] ?? null, self::SUBTITLE_MAX_LENGTH),
                'search_text' => self::truncateString($document['search_text'] ?? null, self::SEARCH_TEXT_MAX_LENGTH),
                'route' => self::truncateString($document['route'] ?? '', self::ROUTE_MAX_LENGTH),
                'metadata' => self::sanitizeMetadata($document['metadata'] ?? []),
                'source_created_at' => $document['created_at'] ?? null,
                'source_updated_at' => $document['updated_at'] ?? null,
            ]
        );
    }

    public static function truncateString(?string $value, int $maxLength): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (mb_strlen($value) <= $maxLength) {
            return $value;
        }

        return mb_substr($value, 0, $maxLength);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private static function sanitizeMetadata(array $metadata): array
    {
        $sanitized = [];

        foreach ($metadata as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = self::truncateString($value, self::TITLE_MAX_LENGTH);

                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
