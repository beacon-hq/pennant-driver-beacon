<?php

declare(strict_types=1);

use Beacon\PennantDriver\BeaconDriver;
use Illuminate\Support\Facades\Http;
use Beacon\PennantDriver\BeaconScope;

it('deletes feature values', function () {
    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->set('test', new BeaconScope(['email' => 'davey@php.net']), true);
    $api->delete('test', new BeaconScope(['email' => 'davey@php.net']));

    expect($this->prop($api, 'resolvedFeatureStates'))
        ->toBe(['test' => []]);
});

it('ignores unknown feature values', function () {
    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->set('test', new BeaconScope(['email' => 'davey@php.net']), true);
    $api->delete('test2', new BeaconScope(['email' => 'davey@php.net']));

    expect($this->prop($api, 'resolvedFeatureStates'))
        ->toBe(['test' => ['{"email":"davey@php.net"}' => true]]);
});
