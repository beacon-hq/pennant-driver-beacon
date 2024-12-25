<?php

declare(strict_types=1);

namespace Tests\Unit;

use Beacon\PennantDriver\BeaconDriver;
use Illuminate\Support\Facades\Http;

it('can retrieve defined features', function () {
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

    expect($api->defined())
        ->toBe(['test', 'test2']);
});

it('returns with no defined features', function () {
    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    expect($api->defined())
        ->toBeEmpty();
});
