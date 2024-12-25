<?php

declare(strict_types=1);

namespace Tests;

use Beacon\PennantDriver\Providers\BeaconDriverServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    protected function prop(object $object, string $property)
    {
        return (fn () => $this->{$property})->call($object);
    }

    protected function getPackageProviders($app)
    {
        return [
            BeaconDriverServiceProvider::class,
        ];
    }
}
