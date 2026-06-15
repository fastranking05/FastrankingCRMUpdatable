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
    | Chat security — read-only data access, rate limits, input/output filtering
    |--------------------------------------------------------------------------
    */
    'security' => [
        'rate_limit_per_minute' => (int) env('AI_CHAT_RATE_LIMIT', 10),
        'status_rate_limit_per_minute' => (int) env('AI_CHAT_STATUS_RATE_LIMIT', 30),
        'max_search_term_length' => 100,
        'audit_log_enabled' => (bool) env('AI_CHAT_AUDIT_LOG', true),
        'blocked_input_patterns' => [
            '/\b(drop|delete|truncate|alter|insert|update|grant|revoke)\s+(table|database|from|into)\b/i',
            '/;\s*--/',
            '/\/\*.*\*\//',
            '/\bignore\s+(all\s+)?(previous|above)\s+instructions\b/i',
            '/\bsystem\s+prompt\b/i',
        ],
        'redact_field_names' => [
            'search_text',
            'route',
            'password',
            'remember_token',
            'api_token',
            'primaryemail',
            'altemail',
            'primary_email',
            'alt_email',
            'primarymobile',
            'altmobile',
            'primary_mobile',
            'alt_mobile',
            'primaryphone',
            'altphone',
            'linkedin_profile',
            'facebook_profile',
        ],
        'mask_field_patterns' => [
            '/email$/i',
            '/phone$/i',
            '/mobile$/i',
            '/token$/i',
            '/secret$/i',
        ],
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
