<?php

declare(strict_types=1);

return [
    'stores' => [
        'beacon' => [
            'driver' => 'beacon',
            'app_name' => env('BEACON_APP_NAME', env('APP_NAME', 'Laravel')),
            'environment' => env('BEACON_ENVIRONMENT', env('APP_ENV', 'local')),
            'url' => env('BEACON_API_URL', 'http://localhost/'),
            'path_prefix' => env('BEACON_API_PATH_PREFIX', '/api'),
            'cache_ttl' => 1800,
            'api_key' => env('BEACON_ACCESS_TOKEN', 'secret'),
        ],
    ],
];
