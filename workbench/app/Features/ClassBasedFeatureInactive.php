<?php

namespace App\Features;

class ClassBasedFeatureInactive
{
    public $name = 'inactive-feature';

    public function resolve()
    {
        return false;
    }
}
