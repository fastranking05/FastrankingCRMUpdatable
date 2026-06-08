<?php

use App\Services\Search\SearchEntityType;

return [
    'enabled' => env('AI_CHAT_ENABLED', false),

    'ollama' => [
        'host' => env('OLLAMA_HOST', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'qwen2.5:7b'),
        'timeout' => (int) env('OLLAMA_TIMEOUT', 120),
    ],

    'chat' => [
        'max_message_length' => 2000,
        'search_result_limit' => 10,
        'recent_per_entity_limit' => 5,
        'reply_language' => env('AI_CHAT_LANGUAGE', 'en'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Global CRM entities — module permission + hierarchy scope per type
    |--------------------------------------------------------------------------
    */
    'entities' => [
        SearchEntityType::BUSINESS => [
            'module' => 'Leads',
            'label' => 'Business / Lead',
            'scope_column' => 'created_by',
        ],
        SearchEntityType::CONTACT => [
            'module' => 'Follow-Up',
            'label' => 'Contact',
            'scope_column' => 'created_by',
        ],
        SearchEntityType::DEAL => [
            'module' => 'Deals',
            'label' => 'Deal',
            'scope_column' => 'created_by',
        ],
        SearchEntityType::APPOINTMENT => [
            'module' => 'Appointment',
            'label' => 'Appointment',
            'scope_column' => 'created_by',
        ],
        SearchEntityType::EMAIL => [
            'module' => 'Email',
            'label' => 'Email',
            'scope_column' => 'created_by',
        ],
        SearchEntityType::CONSULTATION => [
            'module' => 'Consultation',
            'label' => 'Consultation',
            'scope_column' => 'created_by',
        ],
        SearchEntityType::SEO_AUDIT => [
            'module' => 'SEO',
            'label' => 'SEO Audit',
            'scope_column' => 'assigned_user',
        ],
        SearchEntityType::COMMENT => [
            'module' => 'Leads',
            'label' => 'Comment',
            'scope_column' => 'created_by',
        ],
        SearchEntityType::USER => [
            'module' => 'Administration',
            'label' => 'User',
            'scope_column' => 'id',
        ],
    ],

    'extra_summaries' => [
        'followups' => [
            'module' => 'Follow-Up',
            'label' => 'Follow-Up',
        ],
        'quality_audits' => [
            'module' => 'Quality Control',
            'label' => 'Quality Audit',
            'scope_column' => 'assigned_user',
        ],
    ],
];
