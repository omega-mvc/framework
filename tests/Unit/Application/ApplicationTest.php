<?php

declare(strict_types=1);

namespace Tests\Application;

use Omega\Application\AbstractApplication;
use Omega\Application\Application;
use Omega\Config\Bootstrapper\ConfigBootstrapper;
use Omega\Config\ConfigRepository;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Exceptions\HttpException;
use Omega\Http\Request;
use Tests\Application\Fixtures\TestBootstrapProvider;
use Tests\Application\Fixtures\TestServiceProvider;
use Tests\FixturesPathTrait;

covers(Application::class);
covers(AbstractApplication::class);
covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(EntryNotFoundException::class);
covers(HttpException::class);
covers(Request::class);

uses(FixturesPathTrait::class);

it('loads config from default', function (): void {
    $app = new Application(__DIR__);

    $data = [
        'BASEURL'    => '/',
        'APP_DEBUG'  => true,
        'CACHE_STORE' => 'file',
    ];

    $app->loadConfig(new ConfigRepository($data));
    $config = $app->get('config');

    expect($config)->toBeInstanceOf(ConfigRepository::class);
    expect($config->getAll())->toBe($data);

    $app->flush();
});

it('loads environment', function (): void {
    $app = new Application($this->setFixtureBasePath());

    $app->set('environment', 'prod');
    expect($app->isDev())->toBeFalse();
    expect($app->isProduction())->toBeTrue();

    $app->set('environment', 'test');
    expect($app->getenvironment())->toBe('test');

    $app->set('app.debug', false);
    expect($app->isDebugMode())->toBeFalse();

    $app->flush();
});

it('returns the version from configuration', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/application-read/'));

    new ConfigBootstrapper()->bootstrap($app);

    expect($app->getVersion())->toBe('2.0.0');

    $app->flush();
});

it('terminates after the application is done', function (): void {
    $app = new Application('/');
    $app->registerTerminate(static function (): void {
        echo 'terminated.';
    });

    ob_start();
    echo 'application started.';
    echo 'application ended.';
    $app->terminate();
    $out = ob_get_clean();

    expect($out)->toBe('application started.application ended.terminated.');
});

it('aborts the application with an HTTP exception', function (): void {
    new Application(__DIR__)->abort(500);
})->throws(HttpException::class);

it('bootstraps providers with bootstrapWith', function (): void {
    $app = new Application(__DIR__);

    ob_start();
    $app->bootstrapWith([
        TestBootstrapProvider::class,
    ]);
    $out = ob_get_clean();

    expect($out)->toBe('Tests\Application\Fixtures\TestBootstrapProvider::bootstrap');
    expect($app->bootstrapped)->toBeTrue();
});

it('adds callbacks before and after boot', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/application-read/'));

    new ConfigBootstrapper()->bootstrap($app);

    $app->bootedCallback(static function (): void {
        echo 'booted01';
    });
    $app->bootedCallback(static function (): void {
        echo 'booted02';
    });
    $app->bootingCallback(static function (): void {
        echo 'booting01';
    });
    $app->bootingCallback(static function (): void {
        echo 'booting02';
    });

    ob_start();
    $app->bootProvider();
    $out = ob_get_clean();

    expect($out)->toBe('booting01booting02booted01booted02');
    expect($app->isBooted)->toBeTrue();
});

it('adds a callback immediately if the application is already booted', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/application-read/'));

    new ConfigBootstrapper()->bootstrap($app);

    $app->bootProvider();

    ob_start();
    $app->bootedCallback(static function (): void {
        echo 'immediately call';
    });
    $out = ob_get_clean();

    expect($app->isBooted)->toBeTrue();
    expect($out)->toBe('immediately call');
});

it('does not register a provider twice', function (): void {
    $app = new Application('/');

    $app->set('ping', 'pong');

    $app->register(TestServiceProvider::class);
    $app->register(TestServiceProvider::class);

    $test = $app->get('ping');

    expect($test)->toBe('pong');
});

it('returns default down data', function (): void {
    $app = new Application('/');

    expect($app->getDownData())->toBe([
        'redirect' => null,
        'retry'    => null,
        'status'   => 503,
        'template' => null,
    ]);
});

it('returns down data from storage', function (): void {
    $app = new Application($this->setFixtureBasePath());
    $app->set('path.storage', $this->setFixturePath('/fixtures/application-read/storage3/'));

    expect($app->getDownData())->toBe([
        'redirect' => null,
        'retry'    => 15,
        'status'   => 503,
        'template' => null,
    ]);
});

it('detects maintenance mode', function (): void {
    $app = new Application($this->setFixtureBasePath());

    expect($app->isDownMaintenanceMode())->toBeFalse();

    $app->set('path.storage', $this->setFixturePath('/fixtures/application-read/storage/'));

    expect($app->isDownMaintenanceMode())->toBeTrue();
});

it('returns false when the storage path is not a string', function (): void {
    $app = new Application('/');
    $app->set('path.storage', ['array', 'storage']);

    expect($app->isDownMaintenanceMode())->toBeFalse();
});

it('returns the default down data when the storage path is not a string', function (): void {
    $app = new Application('/');
    $app->set('path.storage', ['array', 'storage']);

    expect($app->getDownData())->toBe([
        'redirect' => null,
        'retry'    => null,
        'status'   => 503,
        'template' => null,
    ]);
});

it('terminates with no callbacks', function (): void {
    $this->expectNotToPerformAssertions();

    $app = new Application('/');

    $app->terminate();
});
