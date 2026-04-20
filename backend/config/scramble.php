<?php

use Dedoc\Scramble\Http\Middleware\RestrictedDocsAccess;

return [
    'api_path' => 'api',
    'api_domain' => null,

    'info' => [
        'version' => env('API_VERSION', '1.0.0'),
        'description' => 'KinetoDesk backend API documentation.',
    ],

    'servers' => [
        'Local' => 'api',
    ],

    'middleware' => [
        'web',
        RestrictedDocsAccess::class,
    ],
];