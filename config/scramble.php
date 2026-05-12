<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    // All routes starting with this path will be added to the docs
    'api_path' => env('SCRAMBLE_API_PATH', 'api'),

    // API domain if needed, otherwise null
    'api_domain' => env('SCRAMBLE_API_DOMAIN', null),

    'info' => [
        'version' => env('API_VERSION', '1.0'),
        'description' => env('SCRAMBLE_DESCRIPTION', 'Jawa Barat PMTiles API (POC)'),
    ],

    // By default servers are generated from api_path and api_domain. Set explicit list if needed.
    'servers' => null,

    // Middleware for docs routes. To disable gated access replace RestrictedDocsAccess::class with 'web'.
    'middleware' => [
        // RestrictedDocsAccess::class,
        'web',
    ],

    'extensions' => [],
];
