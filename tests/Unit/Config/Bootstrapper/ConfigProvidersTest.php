<?php

declare(strict_types=1);

namespace Tests\Config\Bootstrapper;

use Omega\Application\Application;
use Omega\Config\Bootstrapper\ConfigBootstrapper;
use Omega\Config\ConfigRepository;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use RuntimeException;
use Tests\FixturesPathTrait;

use function file_exists;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function unlink;

covers(Application::class);
covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(ConfigBootstrapper::class);
covers(EntryNotFoundException::class);

uses(FixturesPathTrait::class);

it('can load config from file', function (): void {
    $app = new Application($this->setFixtureBasePath());
    $app->set('path.config', $this->setFixturePath('/fixtures/application-read/config/'));

    new ConfigBootstrapper()->bootstrap($app);
    $config = $app->get('config');

    expect($config)->toBeInstanceOf(ConfigRepository::class);
    expect($config->get('environment'))->toBe('prod');

    $app->flush();
});

it('can load config from cache', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/application-read/'));

    new ConfigBootstrapper()->bootstrap($app);
    $config = $app->get('config');

    expect($config)->toBeInstanceOf(ConfigRepository::class);
    expect($config->get('environment'))->toBe('prod');

    $app->flush();
});

it('throws exception on invalid config file', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/application-write/'));

    $tempConfigDir = $this->setFixturePath('/fixtures/application-write/config_test/');

    if (!is_dir($tempConfigDir)) {
        mkdir($tempConfigDir, 0777, true);
    }

    $filePath = $tempConfigDir . '/corrupted_config.php';
    file_put_contents($filePath, "<?php return 'Invalid content'; ");

    $app->set('path.config', $tempConfigDir);

    try {
        new ConfigBootstrapper()->bootstrap($app);
    } finally {
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        if (is_dir($tempConfigDir)) {
            rmdir($tempConfigDir);
        }
    }
})->throws(RuntimeException::class, 'Invalid config file');

it('throws if cache is not array', function (): void {
    $basePath = $this->setFixturePath('/fixtures/');

    $app = new Application($basePath);

    $cacheFile = $app->getApplicationCachePath() . 'config.php';

    file_put_contents($cacheFile, "<?php return 'not-an-array';");

    try {
        new ConfigBootstrapper()->bootstrap($app);
    } finally {
        unlink($cacheFile);

        $app->flush();
    }
})->throws(RuntimeException::class, 'Invalid config cache file');

it('loads valid cache', function (): void {
    $basePath = $this->setFixturePath('/fixtures/bootstrap1/');

    $app = new Application($basePath);

    $cacheFile = $app->getApplicationCachePath() . 'config.php';

    file_put_contents($cacheFile, "<?php return ['environment' => 'cached'];");

    new ConfigBootstrapper()->bootstrap($app);

    $config = $app->get('config');

    expect($config)->toBeInstanceOf(ConfigRepository::class);
    expect($config->get('environment'))->toBe('cached');

    unlink($cacheFile);
});

it('returns empty array when no config files found', function (): void {
    $app = new Application($this->setFixturePath('/fixtures/application-write/'));

    $emptyDir = $this->setFixturePath('/fixtures/application-write/empty_config_test/');

    if (!is_dir($emptyDir)) {
        mkdir($emptyDir, 0777, true);
    }

    $app->set('path.config', $emptyDir);

    new ConfigBootstrapper()->bootstrap($app);

    $config = $app->get('config');

    expect($config)->toBeInstanceOf(ConfigRepository::class);
    expect($config->getAll())->toBeEmpty();

    rmdir($emptyDir);
    $app->flush();
});