<?php

declare(strict_types=1);

namespace Beacon\PennantDriver\Providers;

use Beacon\PennantDriver\BeaconDriver;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Laravel\Pennant\Feature;

class BeaconDriverServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->replaceConfigRecursivelyFrom(__DIR__ . '/../../config/pennant.php', 'pennant');
    }

    public function boot(): void
    {
        Feature::extend('beacon', function (Application $app) {
            /** @var Repository $config */
            $config = $app->make('config');

            return new BeaconDriver(
                BeaconDriver::makeClient($config),
                $app->make('events'),
                $config,
                $app->make('cache.store'),
                []
            );
        });
    }
}
