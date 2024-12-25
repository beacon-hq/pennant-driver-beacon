<?php

declare(strict_types=1);

use Beacon\PennantDriver\BeaconDriver;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\EmptyScope;

it('fetches defined features for a given scope', function () {
    Http::fake([
        'features' => Http::response(['features' => ['test', 'test2', 'test3']]),
    ]);

    $config = app()->make('config');

    $api = app()->make(BeaconDriver::class, [
        'client' => BeaconDriver::makeClient($config),
        'featureStateResolvers' => [],
    ]);

    $features = $api->definedFeaturesForScope(new EmptyScope());

    expect($features)
        ->toBe(['test', 'test2', 'test3']);
});

it('returns empty on error', function () {
    Http::fake([
        'features' => Http::response(null, 500),
    ]);

    $config = app()->make('config');

    $api = app()->make(BeaconDriver::class, [
        'client' => BeaconDriver::makeClient($config),
        'featureStateResolvers' => [],
    ]);

    $features = $api->definedFeaturesForScope(new EmptyScope());

    expect($features)
        ->toBe([]);
});
