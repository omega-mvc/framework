<?php

declare(strict_types=1);

namespace Tests\Http;

use Exception;
use Omega\Application\Application;
use Omega\Application\ApplicationManifest;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Http;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;

use function is_string;

uses(FixturesPathTrait::class);

covers(Application::class);
covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(EntryNotFoundException::class);
covers(Http::class);
covers(ApplicationManifest::class);

beforeEach(function (): void {
    $this->app = new Application($this->setFixturePath('/fixtures/application-read/'));

    $this->app->set(ApplicationManifest::class, fn () => new ApplicationManifest(
        basePath: is_string($path = $this->app->get('path.base')) ? $path : '',
        applicationCachePath: $this->app->getApplicationCachePath(),
        vendorPath: '/package/'
    ));

    $this->app->set(
        Http::class,
        fn () => new $this->http($this->app)
    );

    $this->http = new Http($this->app);
});

afterEach(function (): void {
    $this->app->flush();
});

it('can bootstrap', function (): void {
    expect($this->app->bootstrapped)->toBeFalse();
    $http = $this->app->make(Http::class);

    if (!$http instanceof Http) {
        throw new Exception('Expected an Http instance from the container.');
    }

    $http->bootstrap();
    expect($this->app->bootstrapped)->toBeTrue();
});