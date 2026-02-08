<?php

/**
 * Part of Omega - Tests\Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Console\Commands;

use Exception;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Application\Application;
use Omega\Console\Commands\RouteCacheCommand;
use Omega\Router\Router;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;

use function file_exists;
use function ob_get_clean;
use function ob_start;
use function unlink;

/**
 * Test suite for the RouteCache console command.
 *
 * This class verifies the behavior of the `RouteCacheCommand`, which is
 * responsible for generating, caching, and clearing the application's route
 * definitions. The tests cover scenarios such as creating route cache from
 * dynamically defined routes, loading routes from files, handling missing
 * route files, generating a valid cached router, and clearing the route cache.
 *
 * Each test runs in isolation using a real `Application` instance configured
 * with filesystem fixtures. Output buffering is used to capture command output,
 * while assertions validate both execution status codes and the presence of
 * generated route cache files. The `tearDown()` method ensures that any
 * created cache files are removed after each test to maintain a clean environment.
 *
 * This suite ensures that the application's routing cache behaves reliably,
 *
 * @category   Tests
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(RouteCacheCommand::class)]
#[CoversClass(Router::class)]
final class RouteCacheCommandTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $file = $this->setFixturePath(slash(path: '/fixtures/application-write/bootstrap/cache/roue.php'));

        Router::Reset();
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    /**
     * Creates and configures a Router instance for testing.
     *
     * This private helper method sets up a Router with sample routes, including
     * named routes, routes with parameters, prefixed groups, and middleware.
     * It is used in multiple test methods to simulate realistic routing
     * scenarios for cache generation.
     *
     * @return Router Configured Router instance ready for caching.
     */
    private function createRouter(): Router
    {
        $route = new Router();
        $route->get('/test', [__CLASS__, __FUNCTION__])->name('test')->middleware(['test']);
        $route->get('/test/(:id)', [__CLASS__, 'empty']);
        $route->prefix('test/')->group(function () use ($route) {
            $route->post('/test/post', [__CLASS__, 'post'])->name('post');
        });

        return $route;
    }

    /**
     * Test it can create route cache.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCreateRouteCache(): void
    {
        $app = new Application($this->setFixturePath(slash(path: '/fixtures/application-write/')));
        $app->setConfigPath();

        $command = new RouteCacheCommand([]);

        ob_start();
        $status = $command->cache($app, $this->createRouter());
        $out    = ob_get_clean();

        $this->assertEquals(0, $status);
        $this->assertStringContainsString('Route file has successfully created.', $out);
        $this->assertNotEmpty(Router::getRoutes());

        $app->flush();
    }

    /**
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanCreateRouteCacheFromFiles(): void
    {
        $app = new Application($this->setFixturePath(slash(path: '/fixtures/application-read')));
        $app->setConfigPath();

        $command = new RouteCacheCommand(
            argv: [],
            defaultOption: [
                'files' => [['/routes/web.php']],
            ]
        );
        Router::Reset();

        ob_start();
        $status = $command->cache($app, new Router());
        $out    = ob_get_clean();

        $this->assertEquals(0, $status);
        $this->assertStringContainsString('Route file has successfully created.', $out);
        $this->assertNotEmpty(Router::getRoutes());

        $app->flush();
    }

    /**
     * Test it fail create route cache from files.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItFailCreateRouteCacheFromFiles(): void
    {
        $app = new Application($this->setFixturePath(slash(path: '/fixtures/application-write/')));
        $app->setConfigPath();

        $command = new RouteCacheCommand(
            argv: [],
            defaultOption: [
                'files' => [['/routes/api.php']],
            ]
        );
        Router::Reset();

        ob_start();
        $status = $command->cache($app, new Router());
        $out    = ob_get_clean();

        $this->assertEquals(1, $status);
        $this->assertStringContainsString('Route file cant be load \'/routes/api.php\'.', $out);

        $app->flush();
    }


    /**
     * Test it can generate valid route cache.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanGenerateValidRouterCache(): void
    {
        $app = new Application($this->setFixturePath(slash(path: '/fixtures/application-write/')));
        $app->setConfigPath();

        $command = new RouteCacheCommand([]);

        ob_start();
        $status = $command->cache($app, $this->createRouter());
        ob_get_clean();

        $cache_route = require_once $this->setFixturePath(
            slash(path: '/fixtures/application-write/bootstrap/cache/route.php')
        );
        foreach ($cache_route as $route) {
            Router::addRoutes($route);
        }

        $this->assertEquals(0, $status);
        $this->assertNotEmpty(Router::getRoutes());

        $app->flush();
    }

    /**
     * Test it can remove config file.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRemoveConfigFile()
    {
        $app = new Application($this->setFixturePath(slash(path: '/fixtures/application-write/')));
        $app->setConfigPath();

        $command = new RouteCacheCommand([]);

        ob_start();
        $command->cache($app, $this->createRouter());
        $status = $command->clear($app);
        $out    = ob_get_clean();

        $this->assertEquals(0, $status);
        $this->assertStringContainsString('Route file has successfully created.', $out);

        $app->flush();
    }
}
