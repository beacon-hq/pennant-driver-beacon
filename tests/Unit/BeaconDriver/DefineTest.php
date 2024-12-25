<?php

declare(strict_types=1);

namespace Tests\Unit;

use Beacon\PennantDriver\BeaconDriver;
use Closure;
use Illuminate\Support\Facades\Http;

it('defines features', function () {
    $api = app()->make(BeaconDriver::class, [
        'client' => Http::createPendingRequest(),
        'featureStateResolvers' => [],
    ]);

    $api->define('test', function () { return true; });

    expect($this->prop($api, 'featureStateResolvers'))
        ->toHaveCount(1)
        ->toHaveKeys(['test'])
        ->toContainOnlyInstancesOf(Closure::class);
});
