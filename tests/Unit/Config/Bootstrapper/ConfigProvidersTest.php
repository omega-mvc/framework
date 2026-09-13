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


it('can load config from file', function (): void {
    $app = new Application(__DIR__ . '/../fixtures/');
    $app->set('path.config', __DIR__ . '/../fixtures/application-read/config/');

    new ConfigBootstrapper()->bootstrap($app);
    $config = $app->get('config');

    $this->assertInstanceOf(ConfigRepository::class, $config);
    expect($config->get('environment'))->toBe('prod');

    $app->flush();
});

it('can load config from cache', function (): void {
    $app = new Application(__DIR__ . '/../fixtures/application-read/');

    new ConfigBootstrapper()->bootstrap($app);
    $config = $app->get('config');

    $this->assertInstanceOf(ConfigRepository::class, $config);
    expect($config->get('environment'))->toBe('prod');

    $app->flush();
});

it('throws exception on invalid config file', function (): void {
    $app = new Application(__DIR__ . '/../fixtures/application-write/');

    $tempConfigDir = __DIR__ . '/../fixtures/application-write/config_test/';

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
    $basePath = __DIR__ . '/../fixtures/bootstrap-invalid/';

    $cacheDir = $basePath . 'bootstrap/cache/';

    if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0777, true);
    }

    $app = new Application($basePath);

    $cacheFile = $cacheDir . 'config.php';

    file_put_contents($cacheFile, "<?php return 'not-an-array';");

    try {
        new ConfigBootstrapper()->bootstrap($app);
    } finally {
        unlink($cacheFile);
        rmdir($cacheDir);
        rmdir($basePath . 'bootstrap/');
        rmdir($basePath);

        $app->flush();
    }
})->throws(RuntimeException::class, 'Invalid config cache file');

it('loads valid cache', function (): void {
    $basePath = __DIR__ . '/../fixtures/bootstrap1/';

    $app = new Application($basePath);

    $cacheFile = $app->getApplicationCachePath() . 'config.php';

    file_put_contents($cacheFile, "<?php return ['environment' => 'cached'];");

    new ConfigBootstrapper()->bootstrap($app);

    $config = $app->get('config');

    $this->assertInstanceOf(ConfigRepository::class, $config);
    expect($config->get('environment'))->toBe('cached');

    unlink($cacheFile);
});

it('returns empty array when no config files found', function (): void {
    $app = new Application(__DIR__ . '/../fixtures/application-write/');

    $emptyDir = __DIR__ . '/../fixtures/application-write/empty_config_test/';

    if (!is_dir($emptyDir)) {
        mkdir($emptyDir, 0777, true);
    }

    $app->set('path.config', $emptyDir);

    new ConfigBootstrapper()->bootstrap($app);

    $config = $app->get('config');

    $this->assertInstanceOf(ConfigRepository::class, $config);
    expect($config->getAll())->toBeEmpty();

    rmdir($emptyDir);
    $app->flush();
});
