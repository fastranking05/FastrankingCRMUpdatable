<?php

namespace App\Services\Search;

final class SearchEntityType
{
    public const BUSINESS = 'business';
    public const CONTACT = 'contact';
    public const DEAL = 'deal';
    public const APPOINTMENT = 'appointment';
    public const USER = 'user';
    public const EMAIL = 'email';
    public const CONSULTATION = 'consultation';
    public const SEO_AUDIT = 'seo_audit';
    public const COMMENT = 'comment';

    public static function all(): array
    {
        return array_keys(config('global_search.entity_types', []));
    }

    public static function documentId(string $entityType, string|int $entityId): string
    {
        return $entityType . '_' . $entityId;
    }
}
