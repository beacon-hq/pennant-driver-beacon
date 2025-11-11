<?php

declare(strict_types=1);

use Beacon\PennantDriver\BeaconDriver;
use Beacon\PennantDriver\BeaconScope;
use Illuminate\Support\Facades\Http;

it('flushes the cache', function () {
    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->set('test', new BeaconScope(['email' => 'davey@php.net']), true);

    $api->flushCache();

    expect($this->prop($api, 'resolvedFeatureStates'))
        ->toBe([]);
});
