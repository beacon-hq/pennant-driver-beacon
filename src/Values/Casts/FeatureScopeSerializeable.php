<?php

declare(strict_types=1);

namespace Beacon\PennantDriver\Values\Casts;

use Bag\Casts\CastsPropertyGet;
use Illuminate\Support\Collection;
use Laravel\Pennant\Contracts\FeatureScopeSerializeable as FeatureScopeSerializeableInterface;

class FeatureScopeSerializeable implements CastsPropertyGet
{
    public function get(string $propertyName, Collection $properties): mixed
    {
        /** @var FeatureScopeSerializeableInterface $property */
        $property = $properties->get($propertyName);

        return match (true) {
            $property instanceof FeatureScopeSerializeableInterface => $property?->featureScopeSerialize(),
            default => json_encode($property),
        };
    }
}
