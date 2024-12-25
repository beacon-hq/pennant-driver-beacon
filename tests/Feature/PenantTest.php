<?php

declare(strict_types=1);

use App\Models\User;
use Beacon\PennantDriver\BeaconDriver;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Laravel\Pennant\Drivers\Decorator;
use Laravel\Pennant\Feature;
use function Pest\Laravel\actingAs;
use Tests\Fixtures\CustomScope;

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
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(Feature::active('test'))
        ->toBeTrue();

    Http::assertSent(function (Request $request) {
        expect($request->url())
            ->toBe('http://localhost/api/features/test')
            ->and($request->hasHeader('Authorization'))
            ->toBeTrue();

        return true;
    });
});

it('uses the API path prefix with pre and post slashes', function () {
    Config::set('pennant.stores.beacon.path_prefix', '/pennant/');

    Http::fake([
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(Feature::active('test'))
        ->toBeTrue();

    Http::assertSent(function (Request $request) {
        expect($request->url())
            ->toBe('http://localhost/pennant/features/test')
            ->and($request->hasHeader('Authorization'))
            ->toBeTrue();

        return true;
    });
});

it('uses the API path prefix with pre slashes', function () {
    Config::set('pennant.stores.beacon.path_prefix', '/pennant');

    Http::fake([
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(Feature::active('test'))
        ->toBeTrue();

    Http::assertSent(function (Request $request) {
        expect($request->url())
            ->toBe('http://localhost/pennant/features/test')
            ->and($request->hasHeader('Authorization'))
            ->toBeTrue();

        return true;
    });
});

it('uses the API path prefix with post slashes', function () {
    Config::set('pennant.stores.beacon.path_prefix', 'pennant/');

    Http::fake([
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(Feature::active('test'))
        ->toBeTrue();

    Http::assertSent(function (Request $request) {
        expect($request->url())
            ->toBe('http://localhost/pennant/features/test')
            ->and($request->hasHeader('Authorization'))
            ->toBeTrue();

        return true;
    });
});

it('uses the API URL', function () {
    Config::set('pennant.stores.beacon.url', 'http://example.org');

    Http::fake([
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(Feature::active('test'))
        ->toBeTrue();

    Http::assertSent(function (Request $request) {
        expect($request->url())
            ->toBe('http://example.org/api/features/test')
            ->and($request->hasHeader('Authorization'))
            ->toBeTrue();

        return true;
    });
});

it('uses the API URL with trailing slash', function () {
    Config::set('pennant.stores.beacon.url', 'http://example.org/');

    Http::fake([
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(Feature::active('test'))
        ->toBeTrue();

    Http::assertSent(function (Request $request) {
        expect($request->url())
            ->toBe('http://example.org/api/features/test')
            ->and($request->hasHeader('Authorization'))
            ->toBeTrue();

        return true;
    });
});

it('sends default context', function () {
    $user = User::factory()->make(['name' => 'Davey Shafik', 'email' => 'davey@php.net', 'email_verified_at' => '2024-12-24 07:14:27']);

    actingAs($user);

    Http::fake([
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
        expect($request->body())
            ->toBe('{"scopeType":"App\\\\Models\\\\User","scope":"{\"name\":\"Davey Shafik\",\"email\":\"davey@php.net\",\"email_verified_at\":\"2024-12-24T07:14:27.000000Z\"}","appName":"Laravel","environment":"testing","sessionId":null,"ip":"127.0.0.1","userAgent":"Symfony","referrer":null,"url":"http:\/\/localhost","method":"GET"}');

        return true;
    });
});

it('sends custom context', function () {
    Config::set('pennant.default', 'beacon');

    Http::fake([
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function ($scope) {
        expect($scope->scope)
            ->toBe(['email' => 'davey@php.net']);

        return true;
    });

    expect(
        Feature::for(new CustomScope(['email' => 'davey@php.net']))->active('test')
    )->toBeTrue();

    Http::assertSent(function (Request $request) {
        expect($request->body())
            ->toBe('{"scopeType":"Tests\\\\Fixtures\\\\CustomScope","scope":"{\"email\":\"davey@php.net\"}","appName":"Laravel","environment":"testing","sessionId":null,"ip":"127.0.0.1","userAgent":"Symfony","referrer":null,"url":"http:\/\/localhost","method":"GET"}');

        return true;
    });
});

it('returns custom value from API when local value is true', function () {
    Http::fake([
        'test' => Http::response(['active' => true, 'value' => 'custom-value']),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(
        Feature::value('test')
    )->toBe('custom-value');
});

it('does not return custom value from API when local value is false', function () {
    Http::fake([
        'test' => Http::response(['active' => true, 'value' => 'custom-value']),
    ]);

    Feature::define('test', function () {
        return false;
    });

    expect(
        Feature::value('test')
    )->toBeFalse();
});

it('return custom value from local', function () {
    Http::fake([
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function () {
        return 'custom-value';
    });

    expect(
        Feature::value('test')
    )->toBe('custom-value');
});

it('does not return custom value from local', function () {
    Http::fake([
        'test' => Http::response(['active' => true]),
    ]);

    Feature::define('test', function () {
        return false;
    });

    expect(
        Feature::value('test')
    )->toBeFalse();
});

it('does not return custom value when API inactive', function () {
    Http::fake([
        'test' => Http::response(['active' => false]),
    ]);

    Feature::define('test', function () {
        return true;
    });

    expect(
        Feature::value('test')
    )->toBeFalse();
});

it('always fetches from API with no local cache', function () {
    Http::fakeSequence()
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
