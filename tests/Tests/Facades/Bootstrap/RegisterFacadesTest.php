<?php

declare(strict_types=1);

namespace Tests\Facades\Bootstrap;

use Omega\Application\Application;
use Omega\Collection\Collection;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Facade\Bootstrapper\FacadeBootstrapper;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Facades\Bootstrap\Fixtures\TestCollectionFacade;
use Tests\TestCase;

#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Collection::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(FacadeBootstrapper::class)]
final class RegisterFacadesTest extends TestCase
{
    public function testBootstraps(): void
    {
        $app = new Application(basePath: __DIR__ . '/fixtures/');
        $app->set(Collection::class, fn () => new Collection(['php' => 'greater']));
        $app->bootstrapWith([FacadeBootstrapper::class]);

        $this->assertTrue(TestCollectionFacade::has('php'));
    }
}
