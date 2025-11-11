<?php

declare(strict_types=1);

return [
    'stores' => [
        'beacon' => [
            'driver' => 'beacon',
            'app_name' => env('BEACON_APP_NAME', env('APP_NAME', 'Laravel')),
            'environment' => env('BEACON_ENVIRONMENT', env('APP_ENV', 'local')),
            'url' => env('BEACON_API_URL', 'https://api.beaconhq.io/'),
            'path_prefix' => env('BEACON_API_PATH_PREFIX', '/api'),
            'cache_store' => env('BEACON_CACHE_STORE', config('cache.default', 'array')),
            'cache_ttl' => env('BEACON_CACHE_TTL', 1800),
            'api_key' => env('BEACON_ACCESS_TOKEN'),
            'api_timeout' => env('BEACON_API_TIMEOUT', 3000), // We recommend no less than 100ms no greater than 3000ms (3s)
            'debug' => env('BEACON_DEBUG', false),
        ],
    ],
];
