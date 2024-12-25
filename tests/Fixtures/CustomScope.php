<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Laravel\Pennant\Contracts\FeatureScopeSerializeable;

class CustomScope implements FeatureScopeSerializeable
{
    public function __construct(public mixed $scope)
    {
    }

    public function featureScopeSerialize(): string
    {
        return json_encode($this->scope);
    }
}
