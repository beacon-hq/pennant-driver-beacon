<?php

declare(strict_types=1);

use Beacon\PennantDriver\BeaconDriver;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\CustomScope;
use Tests\Fixtures\EmptyScope;

it('it resolves values for active features', function () {
    Http::fake([
        'features/test' => Http::response(['active' => true]),
        'features/test2' => Http::response(['active' => true]),
    ]);

    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->define('test', function () {
        return true;
    });

    $api->define('test2', function () {
        return true;
    });

    $result = $api->get('test', new EmptyScope());

    expect($result)
        ->toBeTrue();


    $result = $api->get('test2', new EmptyScope());

    expect($result)
        ->toBeTrue();
});

it('it resolves values for new features', function () {
    Http::fake([
        'features/test' => Http::response(['active' => false], 201),
        'features/test2' => Http::response(['active' => false], 201),
    ]);

    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->define('test', function () {
        return true;
    });

    $api->define('test2', function () {
        return true;
    });

    $result = $api->get('test', new EmptyScope());

    expect($result)
        ->toBeFalse();


    $result = $api->get('test2', new EmptyScope());

    expect($result)
        ->toBeFalse();
});

it('it uses cached for multiple calls', function () {
    Http::fake([
        'features/test' => Http::response(['active' => true]),
    ]);

    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->define('test', function () {
        return true;
    });

    $api->get('test', new EmptyScope());
    $result = $api->get('test', new EmptyScope());

    expect($result)
        ->toBeTrue();

    Http::assertSentCount(1);
});

it('it returns false for unknown features', function () {
    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $result = $api->get('test', new EmptyScope());

    expect($result)
        ->toBeFalse();
});

it('it respects API active status', function () {
    Http::fake([
        'features/test' => Http::response(['active' => true]),
        'features/test2' => Http::response(['active' => false]),
    ]);

    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->define('test', function () {
        return true;
    });

    $api->define('test2', function () {
        return true;
    });

    $result = $api->get('test', new EmptyScope());

    expect($result)
        ->toBeTrue();

    $result = $api->get('test2', new EmptyScope());

    expect($result)
        ->toBeFalse();
});

it('it gets multiple features', function () {
    Http::fake([
        'features/test' => Http::response(['active' => true]),
        'features/test2' => Http::response(['active' => false]),
    ]);

    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->define('test', function () {
        return true;
    });

    $api->define('test2', function () {
        return true;
    });

    $result = $api->getAll(['test' => [new EmptyScope()], 'test2' => [new EmptyScope()]]);

    expect($result)
        ->toBe([
            'test' => [true],
            'test2' => [false],
        ]);
});

it('it handles API errors', function () {
    Http::fake([
        'features/test' => Http::response(null, 500),
    ]);

    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->define('test', function () {
        return true;
    });

    $result = $api->get('test', new EmptyScope());

    expect($result)
        ->toBeFalse();
});

it('sends scope with request', function () {
    Http::fake([
        'features/test' => Http::response(null, 500),
    ]);

    Config::set('pennant.stores.beacon.api_key', 'secret');

    $config = app()->make('config');

    $api = app()->make(BeaconDriver::class, [
        'client' => BeaconDriver::makeClient($config),
        'featureStateResolvers' => [],
    ]);

    $api->define('test', function () {
        return true;
    });

    $api->get('test', new CustomScope(['email' => 'davey@php.net']));

    Http::assertSent(function (Request $request) {
        expect($request->hasHeader('Authorization'))
            ->toBeTrue()
        ->and($request->header('Authorization')[0])
            ->toBe('Bearer secret')
        ->and($request->url())
            ->toBe('http://localhost/api/features/test')
        ->and($request->body())
            ->toBe('{"scopeType":"Tests\\\\Fixtures\\\\CustomScope","scope":"{\"email\":\"davey@php.net\"}","appName":"Laravel","environment":"testing","sessionId":null,"ip":"127.0.0.1","userAgent":"Symfony","referrer":null,"url":"http:\/\/localhost","method":"GET"}');

        return true;
    });
});
