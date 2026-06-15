<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;

class GlobalSearchDocument extends Model
{
    use Searchable;

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
                'entity_id' => $document['entity_id'],
                'title' => $document['title'],
                'subtitle' => $document['subtitle'],
                'search_text' => $document['search_text'],
                'route' => $document['route'],
                'metadata' => $document['metadata'],
                'source_created_at' => $document['created_at'] ?? null,
                'source_updated_at' => $document['updated_at'] ?? null,
            ]
        );
    }
}
