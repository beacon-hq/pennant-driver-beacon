<?php

declare(strict_types=1);

use App\Features\ClassBasedFeatureActive;
use App\Features\ClassBasedFeatureInactive;
use App\Features\ClassBasedFeatureResolved;
use App\Models\User;
use Beacon\PennantDriver\BeaconDriver;
use Beacon\PennantDriver\BeaconScope;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Http;
use Laravel\Pennant\Drivers\Decorator;
use Laravel\Pennant\Feature;
use function Pest\Laravel\actingAs;

beforeEach(function () {
    Config::set('pennant.default', 'beacon');
});

it('uses the Beacon driver', function () {
    $driver = Feature::driver('beacon');

    expect($driver)
        ->toBeInstanceOf(Decorator::class)
        ->and($this->prop($driver, 'name'))
        ->toBe('beacon')
        ->and($this->prop($driver, 'driver'))
        ->toBeInstanceOf(BeaconDriver::class);
});

it('uses the Beacon API', function () {
    Http::fake([
        'features' => Http::response(['test']),
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(Feature::active('test'))
        ->toBeTrue();

    Http::assertSent(function (Request $request) {
        expect($request->url())
            ->toStartWith('https://api.beacon-hq.dev/api/features')
            ->and($request->hasHeader('Authorization'))
            ->toBeTrue();

        return true;
    });
});

it('uses the API path prefix with pre and post slashes', function () {
    Config::set('pennant.stores.beacon.path_prefix', '/pennant/');

    Http::fake([
        'features' => Http::response(['test']),
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(Feature::active('test'))
        ->toBeTrue();

    Http::assertSent(function (Request $request) {
        expect($request->url())
            ->toStartWith('https://api.beacon-hq.dev/pennant/features')
            ->and($request->hasHeader('Authorization'))
            ->toBeTrue();

        return true;
    });
});

it('uses the API path prefix with pre slashes', function () {
    Config::set('pennant.stores.beacon.path_prefix', '/pennant');

    Http::fake([
        'features' => Http::response(['test']),
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(Feature::active('test'))
        ->toBeTrue();

    Http::assertSent(function (Request $request) {
        expect($request->url())
            ->toStartWith('https://api.beacon-hq.dev/pennant/features')
            ->and($request->hasHeader('Authorization'))
            ->toBeTrue();

        return true;
    });
});

it('uses the API path prefix with post slashes', function () {
    Config::set('pennant.stores.beacon.path_prefix', 'pennant/');

    Http::fake([
        'features' => Http::response(['test']),
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(Feature::active('test'))
        ->toBeTrue();

    Http::assertSent(function (Request $request) {
        expect($request->url())
            ->toStartWith('https://api.beacon-hq.dev/pennant/features')
            ->and($request->hasHeader('Authorization'))
            ->toBeTrue();

        return true;
    });
});

it('uses the API URL', function () {
    Config::set('pennant.stores.beacon.url', 'http://example.org');

    Http::fake([
        'features' => Http::response(['test']),
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(Feature::active('test'))
        ->toBeTrue();

    Http::assertSent(function (Request $request) {
        expect($request->url())
            ->toStartWith('http://example.org/api/features')
            ->and($request->hasHeader('Authorization'))
            ->toBeTrue();

        return true;
    });
});

it('uses the API URL with trailing slash', function () {
    Config::set('pennant.stores.beacon.url', 'http://example.org/');

    Http::fake([
        'features' => Http::response(['test']),
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(Feature::active('test'))
        ->toBeTrue();

    Http::assertSent(function (Request $request) {
        expect($request->url())
            ->toStartWith('http://example.org/api/features')
            ->and($request->hasHeader('Authorization'))
            ->toBeTrue();

        return true;
    });
});

it('sends default context', function () {
    $user = User::factory()->make(['name' => 'Davey Shafik', 'email' => 'davey@php.net', 'email_verified_at' => '2024-12-24 07:14:27']);

    actingAs($user);

    Http::fake([
        'features' => Http::response(['test']),
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function ($scope) use ($user) {
        expect($scope->email)
            ->toBe($user->email);

        return true;
    });

    expect(
        Feature::active('test')
    )->toBeTrue();

    Http::assertSent(function (Request $request) {
        $body = $request->body();
        if ($body === '{"app_name":"Laravel","environment":"local"}') {
            return true;
        }

        expect($body)
            ->toBe('{"scopeType":"App\\\\Models\\\\User","scope":{"name":"Davey Shafik","email":"davey@php.net","email_verified_at":"2024-12-24T07:14:27.000000Z"},"appName":"Laravel","environment":"local","sessionId":null,"ip":"127.0.0.1","userAgent":"Symfony","referrer":null,"url":"http:\/\/localhost","method":"GET"}');

        return true;
    });
});

it('sends custom context', function () {
    Config::set('pennant.default', 'beacon');

    Http::fake([
        'features' => Http::response(['test']),
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function ($scope) {
        expect($scope->scope)
            ->toBe(['email' => 'davey@php.net']);

        return true;
    });

    expect(
        Feature::for(new BeaconScope(['email' => 'davey@php.net']))->active('test')
    )->toBeTrue();

    Http::assertSent(function (Request $request) {
        if ($request->body() === '{"app_name":"Laravel","environment":"local"}') {
            return true;
        }

        expect($request->body())
            ->toBe('{"scopeType":"Beacon\\\\PennantDriver\\\\BeaconScope","scope":{"email":"davey@php.net"},"appName":"Laravel","environment":"local","sessionId":null,"ip":"127.0.0.1","userAgent":"Symfony","referrer":null,"url":"http:\/\/localhost","method":"GET"}');

        return true;
    });
});

it('returns custom value from API when local value is true', function () {
    Http::fake([
        'features' => Http::response(['test']),
        'test' => Http::response(['active' => true, 'value' => 'custom-value']),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(Feature::value('test'))
        ->toBe('custom-value')
        ->and(Context::get('beacon.feature_flags'))
        ->toBe(['test' => ['active' => true, 'value' => 'custom-value']]);
});

it('does not return custom value from API when local value is false', function () {
    Http::fake([
        'features' => Http::response(['test']),
        'test' => Http::response(['active' => true, 'value' => 'custom-value']),
    ]);

    Feature::define('test', function () {
        return false;
    });

    expect(Feature::value('test'))
        ->toBeFalse()
        ->and(Context::get('beacon.feature_flags'))
        ->toBe(['test' => ['active' => false]]);
});

it('return custom value from local', function () {
    Http::fake([
        'test' => Http::response(['active' => true]),
        'features' => Http::response(['test']),
    ]);

    Feature::define('test', function () {
        return 'custom-value';
    });

    expect(Feature::value('test'))
        ->toBe('custom-value')
        ->and(Context::get('beacon.feature_flags'))
        ->toBe(['test' => ['active' => true, 'value' => 'custom-value']]);
});

it('does not return custom value from local', function () {
    Http::fake([
        'test' => Http::response(['active' => true]),
        'features' => Http::response(['test']),
    ]);

    Feature::define('test', function () {
        return false;
    });

    expect(Feature::value('test'))
        ->toBeFalse()
        ->and(Context::get('beacon.feature_flags'))
        ->toBe(['test' => ['active' => false]]);
});

it('does not return custom value when API inactive', function () {
    Http::fake([
        'test' => Http::response(['active' => false]),
        'features' => Http::response(['test']),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(Feature::value('test'))
        ->toBeFalse()
        ->and(Context::get('beacon.feature_flags'))
        ->toBe(['test' => ['active' => false]]);
});

it('always fetches from API with no local cache', function () {
    Http::fakeSequence()
        ->push(['test'])
        ->push(['active' => true])
        ->push(['active' => false]);

    Feature::define('test', function () {
        return true;
    });

    expect(Feature::active('test'))
        ->toBeTrue()
        ->and(Feature::active('test')) // Cache Hit
        ->toBeTrue();

    Feature::purge('test');

    expect(Feature::active('test'))
        ->toBeFalse();

    Http::assertSequencesAreEmpty();
});

it('does makes HTTP request for undefined feature', function () {
    Http::fake([
        'features' => Http::response(['test']),
        'test' => Http::response(['feature_flag' => 'test', 'value' => null, 'active' => false]),
        'test-2' => Http::response(['feature_flag' => 'test-2', 'value' => null, 'active' => true]),
    ]);

    expect(Feature::active('test'))
        ->toBeFalse()
        ->and(Feature::active('test-2'))
        ->toBeTrue()
        ->and(Context::get('beacon.feature_flags'))
        ->toBe(['test' => ['active' => false], 'test-2' => ['active' => true]]);
});

it('resolves class-based features', function () {
    Http::fake([
        'features' => Http::response(['active-feature', 'inactive-feature']),
        'inactive-feature' => Http::response(['active' => false]),
        'active-feature' => Http::response(['active' => true]),
        'resolved-feature' => Http::response(['active' => true]),
    ]);

    expect(Feature::active(ClassBasedFeatureActive::class))
        ->toBeTrue()
        ->and(Feature::active(ClassBasedFeatureInactive::class))
        ->toBeFalse()
        ->and(Feature::active(ClassBasedFeatureResolved::class))
        ->toBeTrue()
        ->and(Context::get('beacon.feature_flags'))
        ->toBe([
            'active-feature' => ['active' => true],
            'inactive-feature' => ['active' => false],
            'resolved-feature' => ['active' => true, 'value' => 'test-value'],
        ]);

    Http::assertSequencesAreEmpty();
});

it('fetches external features', function () {
    Http::fake([
        'features' => Http::response(['test-feature-1', 'test-feature-2', 'test-feature-3']),
        'test-feature-1' => Http::response(['feature_flag' => 'test-feature-1', 'value' => null, 'active' => true]),
        'test-feature-2' => Http::response(['feature_flag' => 'test-feature-2', 'value' => 'test-value', 'active' => true]),
        'test-feature-3' => Http::response(['feature_flag' => 'test-feature-3', 'value' => null, 'active' => false]),
    ]);

    expect(Feature::all())
        ->toBe([
            'test-feature-1' => true,
            'test-feature-2' => 'test-value',
            'test-feature-3' => false,
        ]);
});

it('fetches features dynamically', function () {
    Http::fake([
        'features' => Http::response(['test-feature-1', 'test-feature-2', 'test-feature-3']),
        'test-feature-1' => Http::response(['feature_flag' => 'test-feature-1', 'value' => null, 'active' => true]),
        'test-feature-2' => Http::response(['feature_flag' => 'test-feature-2', 'value' => 'test-value', 'active' => true]),
    ]);

    expect(Feature::active('test-feature-1'))
        ->toBeTrue()
        ->and(Feature::active('test-feature-2'))
        ->toBeTrue()
        ->and(Feature::value('test-feature-2'))
        ->toBe('test-value');
});
