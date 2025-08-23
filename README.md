# Beacon Driver for Laravel Pennant

The official Beacon driver for Laravel Pennant, enabling seamless feature flag management through the Beacon platform.

[Full Documentation](https://beacon-hq.dev/docs/)

## Installation

You can install the package via Composer:

```bash
composer require beacon-hq/pennant-driver
```

The package will automatically register its service provider through Laravel's package auto-discovery.

## Configuration

### Environment Variables

Add the following environment variables to your `.env` file:

```env
BEACON_API_URL=https://your-beacon-instance.com
BEACON_API_TOKEN=your-api-token
BEACON_APP_NAME=YourAppName
BEACON_API_PATH_PREFIX=/api
```

### Pennant Configuration

Update your `config/pennant.php` file to include the Beacon driver:

```php
<?php

return [
    'default' => env('PENNANT_STORE', 'beacon'),

    'stores' => [
        'beacon' => [
            'driver' => 'beacon',
            'app_name' => env('BEACON_APP_NAME', env('APP_NAME', 'Laravel')),
            'environment' => env('BEACON_ENVIRONMENT', env('APP_ENV', 'local')),
            'url' => env('BEACON_API_URL', 'https://api.beacon-hq.dev/'),
            'path_prefix' => env('BEACON_API_PATH_PREFIX', '/api'),
            'cache_store' => env('BEACON_CACHE_STORE', config('cache.default', 'array')),
            'cache_ttl' => env('BEACON_CACHE_TTL', 1800),
            'api_key' => env('BEACON_ACCESS_TOKEN'),
        ],
    ],
];
```

### Configuration Options

- **`app_name`**: The name of your application as registered in Beacon
- **`environment`**: The environment name (e.g., `production`, `staging`, `local`)
- **`url`**: The base URL of your Beacon API instance
- **`path_prefix`**: The API path prefix (default: `/api`)
- **`cache_store`**: The cache store to use (default: Laravel's default cache store)
- **`cache_ttl`**: Cache time-to-live in seconds (default: 1800 seconds / 30 minutes)
- **`api_key`**: Your Beacon API authentication token

## Usage

Once configured, you can use Laravel Pennant's standard API to interact with feature flags:

### Basic Feature Checking

```php
use Laravel\Pennant\Feature;

// Check if a feature is active
if (Feature::active('new-dashboard')) {
    // Show new dashboard
}

// Check if a feature is inactive
if (Feature::inactive('legacy-feature')) {
    // Hide legacy feature
}
```

### Scoped Features

```php
use Laravel\Pennant\Feature;

// Check feature for a specific user
$user = auth()->user();
if (Feature::for($user)->active('premium-features')) {
    // Show premium features
}

// Check feature for a team
$team = $user->team;
if (Feature::for($team)->active('team-collaboration')) {
    // Enable team collaboration features
}
```

### Retrieving Feature Values

```php
use Laravel\Pennant\Feature;

// Get feature value (boolean, string, array, etc.)
$config = Feature::value('feature-config');
```

### Conditional Execution

```php
use Laravel\Pennant\Feature;

// Execute code when feature is active
Feature::when('new-feature', function () {
    // Code to run when feature is active
}, function () {
    // Code to run when feature is inactive (optional)
});
```

## Context Information

The Beacon driver automatically sends contextual information with each request:

- **Scope Type**: The class name of the scoped object (e.g., User, Team)
- **Scope**: The actual scope object or identifier
- **App Name**: Your application name
- **Environment**: Current Laravel environment
- **Session ID**: Current session identifier (if available)
- **IP Address**: Client IP address
- **User Agent**: Client user agent string
- **Referrer**: HTTP referrer header
- **URL**: Current request URL
- **Method**: HTTP request method

This context helps Beacon make intelligent feature flag decisions based on your application's state.

## Caching

The driver includes built-in caching to improve performance:

- Feature responses are cached for the configured TTL (default: 30 minutes)
- Cache keys are generated based on feature names and scope serialization
- You can flush the cache using the driver's `flushCache()` method

## Testing

Run the test suite:

```bash
composer test
```

Run code style checks:

```bash
composer lint
```

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher
- Laravel Pennant 1.14 or higher

## License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## Credits

- [Davey Shafik](https://github.com/dshafik)

## Support

For support, please contact [Beacon HQ](https://beacon-hq.dev) or open an issue on GitHub.
