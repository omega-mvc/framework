<?php

declare(strict_types=1);

namespace Tests\Config\Bootstrapper;

use Omega\Application\Application;
use Omega\Config\Bootstrapper\ConfigBootstrapper;
use Omega\Config\ConfigRepository;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\TestCase;

use function file_exists;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function unlink;

#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(ConfigBootstrapper::class)]
#[CoversClass(EntryNotFoundException::class)]
final class ConfigProvidersTest extends TestCase
{
    public function testCanLoadConfigFromFile(): void
    {
        $app = new Application(__DIR__ . '/../fixtures/');
        $app->set('path.config', __DIR__ . '/../fixtures/application-read/config/');

        new ConfigBootstrapper()->bootstrap($app);
        $config = $app->get('config');

        $this->assertInstanceOf(ConfigRepository::class, $config);
        $this->assertSame('prod', $config->get('environment'));

        $app->flush();
    }

    public function testCanLoadConfigFromCache(): void
    {
        $app = new Application(__DIR__ . '/../fixtures/application-read/');

        new ConfigBootstrapper()->bootstrap($app);
        $config = $app->get('config');

        $this->assertInstanceOf(ConfigRepository::class, $config);
        $this->assertSame('prod', $config->get('environment'));

        $app->flush();
    }

    public function testThrowsExceptionOnInvalidConfigFile(): void
    {
        $app = new Application(__DIR__ . '/../fixtures/application-write/');

        $tempConfigDir = __DIR__ . '/../fixtures/application-write/config_test/';

        if (!is_dir($tempConfigDir)) {
            mkdir($tempConfigDir, 0777, true);
        }

        $filePath = $tempConfigDir . '/corrupted_config.php';
        file_put_contents($filePath, "<?php return 'Invalid content'; ");

        $app->set('path.config', $tempConfigDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid config file');

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
    }

    public function testThrowsIfCacheIsNotArray(): void
    {
        $basePath = __DIR__ . '/../fixtures/bootstrap-invalid/';

        $cacheDir = $basePath . 'bootstrap/cache/';

        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }

        $app = new Application($basePath);

        $cacheFile = $cacheDir . 'config.php';

        file_put_contents($cacheFile, "<?php return 'not-an-array';");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid config cache file');

        try {
            new ConfigBootstrapper()->bootstrap($app);
        } finally {
            unlink($cacheFile);
            rmdir($cacheDir);
            rmdir($basePath . 'bootstrap/');
            rmdir($basePath);

            $app->flush();
        }
    }

    public function testLoadsValidCache(): void
    {
        $basePath = __DIR__ . '/../fixtures/bootstrap1/';

        $app = new Application($basePath);

        $cacheFile = $app->getApplicationCachePath() . 'config.php';

        file_put_contents($cacheFile, "<?php return ['environment' => 'cached'];");

        new ConfigBootstrapper()->bootstrap($app);

        $config = $app->get('config');

        $this->assertInstanceOf(ConfigRepository::class, $config);
        $this->assertSame('cached', $config->get('environment'));

        unlink($cacheFile);
    }

    public function testReturnsEmptyArrayWhenNoConfigFilesFound(): void
    {
        $app = new Application(__DIR__ . '/../fixtures/application-write/');

        $emptyDir = __DIR__ . '/../fixtures/application-write/empty_config_test/';

        if (!is_dir($emptyDir)) {
            mkdir($emptyDir, 0777, true);
        }

        $app->set('path.config', $emptyDir);

        new ConfigBootstrapper()->bootstrap($app);

        $config = $app->get('config');

        $this->assertInstanceOf(ConfigRepository::class, $config);
        $this->assertEmpty($config->getAll());

        rmdir($emptyDir);
        $app->flush();
    }
}
