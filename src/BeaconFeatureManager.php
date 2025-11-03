<?php

declare(strict_types=1);

namespace Beacon\PennantDriver;

use Illuminate\Contracts\Container\Container;
use Laravel\Pennant\FeatureManager;

class BeaconFeatureManager extends FeatureManager
{
    public function __construct(Container $container, FeatureManager $original)
    {
        parent::__construct($container);

        $this->stores = $original->stores;
        $this->customCreators = $original->customCreators;
        $this->defaultScopeResolver = $original->defaultScopeResolver;
        $this->useMorphMap = $original->useMorphMap;
    }

    public function define($feature, $resolver = null): void
    {
        if ($resolver === null) {
            $resolver = BeaconDriver::useRemotePolicy();
        }

        parent::define($feature, $resolver);
    }
}
