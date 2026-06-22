<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Global Search (Typesense + Laravel Scout)
    |--------------------------------------------------------------------------
    */

    'enabled' => env('GLOBAL_SEARCH_ENABLED', env('ELASTICSEARCH_ENABLED', true)),

    /*
    | When Typesense is down, search falls back to database queries.
    | Set false to return 503 until Typesense is available.
    */
    'fallback_to_database' => env('GLOBAL_SEARCH_FALLBACK_DATABASE', env('ELASTICSEARCH_FALLBACK_DATABASE', true)),

    'collection' => env('GLOBAL_SEARCH_COLLECTION', env('ELASTICSEARCH_INDEX', 'fastranking_global_search')),

    'timeout' => (int) env('GLOBAL_SEARCH_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Global Search Defaults
    |--------------------------------------------------------------------------
    */

    'search' => [
        'min_query_length' => 2,
        'default_limit' => 20,
        'max_limit' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Searchable Entity Types
    |--------------------------------------------------------------------------
    */

    'entity_types' => [
        'business' => [
            'label' => 'Business / Lead',
            'model' => \App\Models\FollowupBusiness::class,
        ],
        'contact' => [
            'label' => 'Contact',
            'model' => \App\Models\FollowupAuthPerson::class,
        ],
        'deal' => [
            'label' => 'Deal',
            'model' => \App\Models\Deal::class,
        ],
        'appointment' => [
            'label' => 'Appointment',
            'model' => \App\Models\Appointment::class,
        ],
        'user' => [
            'label' => 'User',
            'model' => \App\Models\User::class,
        ],
        'email' => [
            'label' => 'Email',
            'model' => \App\Models\Email::class,
        ],
        'consultation' => [
            'label' => 'Consultation',
            'model' => \App\Models\Consultation::class,
        ],
        'seo_audit' => [
            'label' => 'SEO Audit',
            'model' => \App\Models\SeoDetail::class,
        ],
        'comment' => [
            'label' => 'Comment',
            'model' => \App\Models\Comment::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity Type → CRM Module Permission Map
    |--------------------------------------------------------------------------
    | Global search only returns entity types when the user has can_read on
    | at least one mapped module via their department(s).
    */
    'entity_module_map' => [
        'business' => ['Leads', 'Follow-Up'],
        'contact' => ['Leads', 'Follow-Up'],
        'deal' => ['Deals'],
        'appointment' => ['Appointment'],
        'user' => ['Administration'],
        'email' => ['Email'],
        'consultation' => ['Consultation'],
        'seo_audit' => ['SEO'],
        'comment' => ['Leads', 'Follow-Up', 'Deals', 'Appointment'],
    ],

];
