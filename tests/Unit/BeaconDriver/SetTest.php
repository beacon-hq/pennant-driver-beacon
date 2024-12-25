<?php

declare(strict_types=1);

use Beacon\PennantDriver\BeaconDriver;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\CustomScope;

it('sets values for scope', function () {
    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->set('test', new CustomScope(['email' => 'davey@php.net']), true);

    expect($this->prop($api, 'resolvedFeatureStates'))
        ->toBe(['test' => ['{"email":"davey@php.net"}' => true]]);
});

it('sets values for multiple scopes', function () {
    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->set('test', new CustomScope(['email' => 'davey@php.net']), true);
    $api->set('test2', new CustomScope(['email' => 'davey@php.net']), false);

    expect($this->prop($api, 'resolvedFeatureStates'))
        ->toBe([
            'test' => ['{"email":"davey@php.net"}' => true],
            'test2' => ['{"email":"davey@php.net"}' => false]
        ]);
});

it('overrides values for same scope', function () {
    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->set('test', new CustomScope(['email' => 'davey@php.net']), true);
    $api->set('test', new CustomScope(['email' => 'davey@php.net']), false);

    expect($this->prop($api, 'resolvedFeatureStates'))
        ->toBe(['test' => ['{"email":"davey@php.net"}' => false]]);
});
