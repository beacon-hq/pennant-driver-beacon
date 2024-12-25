<?php

declare(strict_types=1);

use Beacon\PennantDriver\BeaconDriver;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\EmptyScope;

it('retrieves empty features from storage', function () {
    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    expect($api->stored())
        ->toBeArray()
        ->toBeEmpty();
});

it('retrieves features from storage', function () {
    Http::fake([
        '*' => Http::response([]),
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

    $api->get('test', new EmptyScope());

    expect($api->stored())
        ->toBe(['test']);
});
