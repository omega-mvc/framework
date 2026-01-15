<?php

/**
 * Part of Omega - Tests\Support\Bootstrap Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Support\Bootstrap;

use Exception;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Support\Bootstrap\ConfigProviders;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

/**
 * Class ConfigProvidersTest
 *
 * This test suite verifies that the ConfigProviders bootstrapper correctly loads
 * configuration values into the application container. It tests two scenarios:
 *
 * 1. Loading configuration directly from configuration files when no cache is present.
 * 2. Loading configuration from a pre-generated cache file when available.
 *
 * These tests ensure that the application's configuration system behaves consistently
 * and that configuration values are properly accessible through the container once
 * bootstrapped.
 *
 * @category   Tests
 * @package    Support
 * @subpackage Bootstrap
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(ConfigProviders::class)]
#[CoversClass(EntryNotFoundException::class)]
class ConfigProvidersTest extends TestCase
{
    /**
     * Test it can load config from file.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception if a generic error occurred
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanLoadConfigFromFile(): void
    {
        $app = new Application(basePath: __DIR__);
        $app->set('path.config', slash(path: __DIR__ . '/fixtures/config/'));

        new ConfigProviders()->bootstrap($app);
        $config = $app->get('config');

        $this->assertEquals('prod', $config->get('environment'));

        $app->flush();
    }

    /**
     * Test it can load config from cache.
     *
     * Assume this test is boostrap application.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception if a generic error occurred
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanLoadConfigFromCache(): void
    {
        $app = new Application(basePath: __DIR__ . '/fixtures');

        new ConfigProviders()->bootstrap($app);
        $config = $app->get('config');

        $this->assertEquals('prod', $config->get('environment'));

        $app->flush();
    }
}
