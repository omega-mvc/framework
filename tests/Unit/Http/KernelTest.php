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
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function is_string;

#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Http::class)]
#[CoversClass(ApplicationManifest::class)]
final class KernelTest extends TestCase
{
    private Http $http;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application(__DIR__ . '/fixtures/application-read/');

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
    }

    protected function tearDown(): void
    {
        $this->app->flush();

        parent::tearDown();
    }

    public function testCanBootstrap(): void
    {
        $this->assertFalse($this->app->bootstrapped);
        $http = $this->app->make(Http::class);

        if (!$http instanceof Http) {
            throw new Exception('Expected an Http instance from the container.');
        }

        $http->bootstrap();
        $this->assertTrue($this->app->bootstrapped);
    }
}
