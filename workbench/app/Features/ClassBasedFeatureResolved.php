<?php

declare(strict_types=1);

namespace App\Features;

class ClassBasedFeatureResolved
{
    public $name = 'resolved-feature';

    public function resolve()
    {
        return 'test-value';
    }
}
