<?php

declare(strict_types=1);

use Beacon\PennantDriver\BeaconDriver;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\CustomScope;

it('flushes the cache', function () {
    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->set('test', new CustomScope(['email' => 'davey@php.net']), true);

    $api->flushCache();

    expect($this->prop($api, 'resolvedFeatureStates'))
        ->toBe([]);
});
