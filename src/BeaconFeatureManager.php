<?php

declare(strict_types=1);

namespace Beacon\PennantDriver;

use Laravel\Pennant\FeatureManager;

class BeaconFeatureManager extends FeatureManager
{
    public function define($feature, $resolver = null): void
    {
        if ($resolver === null) {
            $resolver = BeaconDriver::useRemotePolicy();
        }

        parent::define($feature, $resolver);
    }
}
