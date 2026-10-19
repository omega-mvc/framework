<?php

declare(strict_types=1);

namespace Tests\Application\Bootstrapper;

use Omega\Application\Application;
use Omega\Application\Bootstrapper\BootProviders;
use Omega\Config\Bootstrapper\ConfigBootstrapper;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Application\Bootstrapper\Fixtures\BootCalledServiceProvider;
use Tests\TestCase;

#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(BootProviders::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
final class BootProvidersTest extends TestCase
{
    public function testBootsTheApplicationViaBootProviders(): void
    {
        $app = new Application(__DIR__ . '/../fixtures/application-read/');

        $this->assertFalse($app->isBooted);
        $app->bootstrapWith([ConfigBootstrapper::class, BootProviders::class]);
        $this->assertTrue($app->isBooted);
    }

    public function testBootsRegisteredProviderImmediatelyWhenApplicationIsAlreadyBooted(): void
    {
        $app = new Application(__DIR__ . '/../fixtures/application-read/');

        $app->isBooted = true;

        $registered = $app->register(BootCalledServiceProvider::class);

        $this->assertInstanceOf(BootCalledServiceProvider::class, $registered);
        $this->assertTrue($registered->bootCalled);
    }

    public function testDoesNotBootRegisteredProviderWhenApplicationIsNotBooted(): void
    {
        $app = new Application(__DIR__ . '/../fixtures/application-read/');

        $registered = $app->register(BootCalledServiceProvider::class);

        $this->assertInstanceOf(BootCalledServiceProvider::class, $registered);
        $this->assertFalse($registered->bootCalled);
    }
}