<?php

namespace App\Features;

class ClassBasedFeatureActive
{
    public string $name = 'active-feature';

    public function resolve()
    {
        return true;
    }
}
