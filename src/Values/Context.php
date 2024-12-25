<?php

declare(strict_types=1);

namespace Beacon\PennantDriver\Values;

use Bag\Attributes\CastOutput;
use Bag\Bag;
use Beacon\PennantDriver\Values\Casts\FeatureScopeSerializeable;
use Laravel\Pennant\Contracts\FeatureScopeSerializeable as FeatureScopeSerializeableInterface;

/**
 * @method static static from(string $scopeType, mixed $scope, string $appName, string $environment, ?string $sessionId, ?string $ip, ?string $userAgent, ?string $referrer, ?string $url, ?string $method)
 */
readonly class Context extends Bag implements FeatureScopeSerializeableInterface
{
    public function __construct(
        public ?string $scopeType,
        #[CastOutput(FeatureScopeSerializeable::class)]
        public mixed $scope,
        public string $appName,
        public string $environment,
        public ?string $sessionId,
        public ?string $ip,
        public ?string $userAgent,
        public ?string $referrer,
        public ?string $url,
        public ?string $method,
    ) {
    }

    public function featureScopeSerialize(): string
    {
        return $this->toJson();
    }
}
