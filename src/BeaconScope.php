<?php

declare(strict_types=1);

namespace Beacon\PennantDriver;

use JsonSerializable;
use Laravel\Pennant\Contracts\FeatureScopeSerializeable;

class BeaconScope implements FeatureScopeSerializeable, JsonSerializable
{
    public function __construct(public mixed $scope) {}

    public function featureScopeSerialize(): string
    {
        return json_encode($this->scope);
    }

    public function jsonSerialize(): mixed
    {
        return $this->scope;
    }
}
