<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Connection
    |--------------------------------------------------------------------------
    */

    'enabled' => env('ELASTICSEARCH_ENABLED', true),

    /*
    | When Elasticsearch is down, search falls back to database queries.
    | Set false to return 503 until Elasticsearch is available.
    */
    'fallback_to_database' => env('ELASTICSEARCH_FALLBACK_DATABASE', true),

    'hosts' => [
        env('ELASTICSEARCH_HOST', 'http://127.0.0.1:9200'),
    ],

    'username' => env('ELASTICSEARCH_USERNAME'),
    'password' => env('ELASTICSEARCH_PASSWORD'),

    'index' => env('ELASTICSEARCH_INDEX', 'fastranking_global_search'),

    'timeout' => (int) env('ELASTICSEARCH_TIMEOUT', 10),

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

];
