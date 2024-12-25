<?php

declare(strict_types=1);

use Beacon\PennantDriver\Values\Context;
use Tests\Fixtures\CustomScope;

it('serializes data', function () {
    $scope = new CustomScope(['foo' => 'bar']);

    $context = new Context(
        scopeType: $scope::class,
        scope: $scope,
        appName: 'app_name',
        environment: 'testing',
        sessionId: 'session_id',
        ip: 'ip',
        userAgent: 'user_agent',
        referrer: 'referrer',
        url: 'url',
        method: 'method'
    );

    expect($context->featureScopeSerialize())
        ->toBe('{"scopeType":"Tests\\\\Fixtures\\\\CustomScope","scope":"{\"foo\":\"bar\"}","appName":"app_name","environment":"testing","sessionId":"session_id","ip":"ip","userAgent":"user_agent","referrer":"referrer","url":"url","method":"method"}');
});
