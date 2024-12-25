<?php

declare(strict_types=1);

use Beacon\PennantDriver\BeaconDriver;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\EmptyScope;

it('purges single features', function () {
    Http::fake([
        'features/test' => Http::response(),
    ]);

    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->set('test', new EmptyScope(), true);

    $api->purge(['test']);

    expect($this->prop($api, 'resolvedFeatureStates'))
        ->toBe([]);

    Http::assertSequencesAreEmpty();
});

it('purges multiple features', function () {
    Http::fake([
        'features/test' => Http::response(),
        'features/test2' => Http::response(),
    ]);

    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->set('test', new EmptyScope(), true);
    $api->set('test2', new EmptyScope(), true);
    $api->set('test3', new EmptyScope(), true);

    $api->purge(['test', 'test2']);

    expect($this->prop($api, 'resolvedFeatureStates'))
        ->toBe(['test3' => ['a:0:{}' => true]]);

    Http::assertSequencesAreEmpty();
});

it('purges all features', function () {
    Http::fake([
        'features' => Http::response(),
    ]);

    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->set('test', new EmptyScope(), true);
    $api->set('test2', new EmptyScope(), true);

    $api->purge(null);

    expect($this->prop($api, 'resolvedFeatureStates'))
        ->toBe([]);

    Http::assertSequencesAreEmpty();
});
