<?php

declare(strict_types=1);

namespace Beacon\PennantDriver;

use Beacon\PennantDriver\Values\Context;
use Closure;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context as LaravelContext;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Pennant\Contracts\CanListStoredFeatures;
use Laravel\Pennant\Contracts\DefinesFeaturesExternally;
use Laravel\Pennant\Contracts\Driver;
use Laravel\Pennant\Contracts\FeatureScopeSerializeable;
use Laravel\Pennant\Contracts\HasFlushableCache;
use Laravel\Pennant\Events\UnknownFeatureResolved;
use Laravel\Pennant\Feature;
use stdClass;

class BeaconDriver implements CanListStoredFeatures, DefinesFeaturesExternally, Driver, HasFlushableCache
{
    /**
     * The resolved feature states.
     *
     * @var array<string, array<string, mixed>>
     */
    protected $resolvedFeatureStates = [];

    /**
     * The sentinel value for unknown features.
     *
     * @var stdClass
     */
    protected $unknownFeatureValue;

    /**
     * Create a new driver instance.
     *
     * @param  array<string, (callable(mixed $scope): mixed)>  $featureStateResolvers
     * @return void
     */
    public function __construct(
        protected PendingRequest $client,
        protected Dispatcher $events,
        protected ConfigRepository $config,
        protected CacheRepository $cache,
        protected array $featureStateResolvers,
    ) {
        $this->unknownFeatureValue = new stdClass();
    }

    public static function useRemotePolicy(): Closure
    {
        return function () {
            return true;
        };
    }

    /**
     * Define an initial feature flag state resolver.
     *
     * @param  (callable(mixed $scope): mixed)  $resolver
     */
    public function define(string $feature, callable $resolver): void
    {
        $this->featureStateResolvers[$feature] = function (mixed $scope) use ($feature, $resolver) {
            try {
                if (class_exists($feature)) {
                    $feature = app()->make($feature)->name ?? $feature;
                }

                $result = $this->getFeature($scope, $feature);
            } catch (RequestException) {
                $this->events->dispatch(new UnknownFeatureResolved($feature, $scope));

                return $this->unknownFeatureValue;
            }

            $resolved = false;
            if ($result['active'] ?? false) {
                $resolved = $resolver($scope);

                if ($resolved === true && ($result['value'] ?? null) !== null) {
                    $resolved = $result['value'];
                }
            }

            $this->resolvedFeatureStates[$feature] ??= [];
            $this->resolvedFeatureStates[$feature][Feature::serializeScope($scope)] = $resolved;

            $this->addToContext($feature, $resolved);

            return $resolved;
        };
    }

    /**
     * Retrieve the names of all defined features.
     *
     * @return array<string>
     */
    public function defined(): array
    {
        return $this->definedFeaturesForScope(null);
    }

    /**
     * Retrieve the names of all stored features.
     *
     * @return array<string>
     */
    public function stored(): array
    {
        return array_keys($this->resolvedFeatureStates);
    }

    /**
     * Get multiple feature flag values.
     *
     * @param  array<string, array<int, mixed>>  $features
     * @return array<string, array<int, mixed>>
     */
    public function getAll(array $features): array
    {
        return Collection::make($features)
            ->map(function ($scopes, $feature) {
                return Collection::make($scopes)
                    ->map(function ($scope) use ($feature) {
                        return $this->get($feature, $scope);
                    })
                    ->all();
            })
            ->all();
    }

    /**
     * Retrieve a feature flag's value.
     *
     * @param  FeatureScopeSerializeable  $scope
     */
    public function get(string $feature, mixed $scope): mixed
    {
        $scopeKey = Feature::serializeScope($scope);

        if (isset($this->resolvedFeatureStates[$feature][$scopeKey])) {
            return $this->resolvedFeatureStates[$feature][$scopeKey];
        }

        return with($this->resolveValue($feature, $scope), function ($value) use ($feature, $scopeKey) {
            if ($value === $this->unknownFeatureValue) {
                return false;
            }

            $this->set($feature, $scopeKey, $value);

            return $value;
        });
    }

    /**
     * Determine the initial value for a given feature and scope.
     */
    protected function resolveValue(string $feature, mixed $scope): mixed
    {
        if ($this->missingResolver($feature)) {
            $this->define($feature, static::useRemotePolicy());
        }

        return $this->featureStateResolvers[$feature]($scope);
    }

    /**
     * Set a feature flag's value.
     */
    public function set(string $feature, mixed $scope, mixed $value): void
    {
        $this->resolvedFeatureStates[$feature] ??= [];

        $this->resolvedFeatureStates[$feature][Feature::serializeScope($scope)] = $value;
    }

    /**
     * Set a feature flag's value for all scopes.
     */
    public function setForAllScopes(string $feature, mixed $value): void
    {
        $this->resolvedFeatureStates[$feature] ??= [];

        foreach ($this->resolvedFeatureStates[$feature] as $scope => $currentValue) {
            $this->resolvedFeatureStates[$feature][$scope] = $value;
        }
    }

    /**
     * Delete a feature flag's value.
     */
    public function delete(string $feature, mixed $scope): void
    {
        unset($this->resolvedFeatureStates[$feature][Feature::serializeScope($scope)]);
    }

    /**
     * Purge the given feature from storage.
     */
    public function purge(?array $features): void
    {
        if ($features === null) {
            $this->resolvedFeatureStates = [];
            $this->flushCache();
        } else {
            foreach ($features as $feature) {
                foreach ($this->resolvedFeatureStates[$feature] as $scope => $_) {
                    $this->cache->tags(['beacon', 'feature-flag'])->forget($feature.':'.hash('sha256', $scope));
                }
                unset($this->resolvedFeatureStates[$feature]);
            }
        }
    }

    /**
     * Determine if the feature does not have a resolver available.
     */
    protected function missingResolver(string $feature): bool
    {
        return ! array_key_exists($feature, $this->featureStateResolvers);
    }

    /**
     * Flush the resolved feature states.
     */
    public function flushCache(): void
    {
        $this->cache->tags(['beacon'])->forget('pennant-features');
        $this->cache->tags(['beacon', 'feature-flag'])->flush();

        $this->resolvedFeatureStates = [];
    }

    public static function makeClient(Repository $config)
    {
        return Http::baseUrl(Str::start(Str::chopStart($config->get('pennant.stores.beacon.path_prefix'), '/'), Str::finish($config->get('pennant.stores.beacon.url'), '/')))
            ->withHeaders([
                'Accept' => 'application/json',
            ])
            ->withToken($config->get('pennant.stores.beacon.api_key'))
            ->timeout($config->get('pennant.stores.beacon.api_timeout', 3000) / 1000);
    }

    public function getFeature(mixed $scope, string $feature): array
    {
        $context = $this->getContext($scope);

        $featureName = Str::slug($feature);

        try {
            $cacheTtl = $this->config->get('pennant.stores.beacon.cache_ttl');

            return $this->cache->tags(['beacon', 'feature-flag'])->flexible($featureName.':'.hash('sha256', Feature::serializeScope($scope)), [$cacheTtl, $cacheTtl + 30], function () use ($context, $featureName) {
                return $this->client->post('/features/'.$featureName, $context->toArray())->throw()->json();
            });
        } catch (ConnectionException|RequestException) {
            return [
                'feature-flag' => $featureName,
                'value' => null,
                'active' => false,
            ];
        }
    }

    public function getFeatures(mixed $scope): array
    {
        $context = $this->getContext($scope);

        return $this->cache->tags(['beacon'])->flexible('pennant-features', [$this->config->get('pennant.stores.beacon.cache_ttl'), 30], function () use ($context) {
            return $this->client->post('/features', $context->toArray())->throw()->json();
        });
    }

    protected function getContext(mixed $scope): Context
    {
        $context = Context::from(
            scopeType: is_object($scope) ? Feature::serializeScope($scope::class) : null,
            scope: $scope,
            appName: $this->config->get('pennant.stores.beacon.app_name', $this->config->get('app.name')),
            environment: $this->config->get('pennant.stores.beacon.environment', $this->config->get('app.env')),
            sessionId: request()?->hasSession() ? request()->session()->getId() : null,
            ip: request()?->ip(),
            userAgent: request()?->userAgent(),
            referrer: request()?->headers->get('referer'),
            url: request()?->url(),
            method: request()?->method(),
        );

        return $context;
    }

    protected function addToContext(string $feature, mixed $resolved): void
    {
        LaravelContext::add(
            'beacon.feature_flags',
            collect(LaravelContext::get('beacon.feature_flags', []))
                ->put($feature, [
                    'active' => $resolved !== false,
                    ...(! is_bool($resolved) ? ['value' => $resolved] : []),
                ])
                ->toArray(),
        );
    }

    public function definedFeaturesForScope(mixed $scope): array
    {
        $cacheTtl = $this->config->get('pennant.stores.beacon.cache_ttl');

        $context = $this->getContext($scope);

        try {
            return $this->cache->tags(['beacon', 'feature-flags'])->flexible('pennant-features-for-scope:'.$context->appName.':'.$context->environment, [$cacheTtl, $cacheTtl + 30], function () use ($context) {
                $features = $this->client->post('/features', ['app_name' => $context->appName, 'environment' => $context->environment])->throw()->json();
                collect($features)->each(function ($feature) {
                    if (! isset($this->featureStateResolvers[$feature])) {
                        Feature::define($feature, static::useRemotePolicy());
                    }
                });

                return $features;
            });
        } catch (RequestException|ConnectionException) {
            return [];
        }
    }
}
