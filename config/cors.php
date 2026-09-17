<?php

// Tech-debt #9: overrides vendor default ('allowed_origins' => ['*']) with a
// production whitelist. Set CORS_ALLOWED_ORIGINS (comma-separated) to add
// origins (e.g. local dev tools) without editing this file.
$extraOrigins = array_filter(array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', ''))));

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_merge([
        'https://kranpay.pdam.local',
        'http://kranpay.pdam.local',
        'https://api.pdam.local',
    ], $extraOrigins))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
