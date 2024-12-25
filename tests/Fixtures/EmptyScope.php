<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Laravel\Pennant\Contracts\FeatureScopeSerializeable;

class EmptyScope implements FeatureScopeSerializeable
{
    public function featureScopeSerialize(): string
    {
        return serialize([]);
    }
}
