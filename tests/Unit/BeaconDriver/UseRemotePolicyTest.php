<?php

declare(strict_types=1);

use Beacon\PennantDriver\BeaconDriver;

it('it returns true', function () {
    expect(BeaconDriver::useRemotePolicy()())->toBeTrue();
});
