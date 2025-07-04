<?php

declare(strict_types=1);

namespace Beacon\PennantDriver\Values\Casts;

use Bag\Bag;
use Bag\Casts\CastsPropertyGet;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Illuminate\Support\Collection;
use JsonSerializable;
use Laravel\Pennant\Contracts\FeatureScopeSerializeable as FeatureScopeSerializeableInterface;

class FeatureScopeSerializeable implements CastsPropertyGet
{
    public function get(string $propertyName, Collection $properties): mixed
    {
        /** @var FeatureScopeSerializeableInterface $property */
        $property = $properties->get($propertyName);

        return match (true) {
            $property instanceof JsonSerializable, $property instanceof Bag => $property->jsonSerialize(),
            $property instanceof Arrayable => $property->toArray(),
            default => $property,
        };
    }
}
