<?php

declare(strict_types=1);

namespace Tests\Application\Bootstrapper;

use Omega\Application\Application;
use Omega\Application\Bootstrapper\BootProviders;
use Omega\Config\Bootstrapper\ConfigBootstrapper;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Tests\Application\Bootstrapper\Fixtures\BootCalledServiceProvider;
use Tests\FixturesPathTrait;

covers(Application::class);
covers(BindingResolutionException::class);
covers(BootProviders::class);
covers(CircularAliasException::class);
covers(EntryNotFoundException::class);

uses(FixturesPathTrait::class);

it('boots the application via BootProviders', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/application-read/'));

    expect($app->isBooted)->toBeFalse();
    $app->bootstrapWith([ConfigBootstrapper::class, BootProviders::class]);
    expect($app->isBooted)->toBeTrue();
});

it('boots a registered provider immediately when the application is already booted', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/application-read/'));

    $app->isBooted = true;

    $registered = $app->register(BootCalledServiceProvider::class);

    expect($registered)->toBeInstanceOf(BootCalledServiceProvider::class);
    expect($registered->bootCalled)->toBeTrue();
});

it('does not boot a registered provider when the application is not booted', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/application-read/'));

    $registered = $app->register(BootCalledServiceProvider::class);

    expect($registered)->toBeInstanceOf(BootCalledServiceProvider::class);
    expect($registered->bootCalled)->toBeFalse();
});
