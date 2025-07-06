<?php

declare(strict_types=1);

use Beacon\PennantDriver\BeaconFeatureManager;
use Beacon\PennantDriver\Providers\BeaconDriverServiceProvider;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Laravel\Pennant\Feature;

beforeEach(function () {
    $this->app = new Application();
    $this->config = new ConfigRepository([]);
    $this->app->instance('config', $this->config);
});

it('merges configuration on register', function () {
    $provider = new BeaconDriverServiceProvider($this->app);
    $provider->register();

    expect($this->config->all())->toHaveKey('pennant');
});

it('replaces default FeatureManager with BeaconFeatureManager', function () {
    expect(Feature::getFacadeRoot())
        ->toBeInstanceOf(BeaconFeatureManager::class);
});
