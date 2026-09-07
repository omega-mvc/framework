<?php

declare(strict_types=1);

namespace Tests\Facades\Bootstrap;

use Omega\Application\Application;
use Omega\Collection\Collection;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Facade\Bootstrapper\FacadeBootstrapper;
use Tests\Facades\Bootstrap\Fixtures\TestCollectionFacade;

use function expect;

covers(Application::class);
covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(Collection::class);
covers(EntryNotFoundException::class);
covers(FacadeBootstrapper::class);

it('bootstraps', function (): void {
    $app = new Application(basePath: __DIR__ . '/fixtures/');
    $app->set(Collection::class, fn () => new Collection(['php' => 'greater']));
    $app->bootstrapWith([FacadeBootstrapper::class]);

    expect(TestCollectionFacade::has('php'))->toBeTrue();
});