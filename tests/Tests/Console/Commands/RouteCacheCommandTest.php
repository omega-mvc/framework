<?php

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

use function dirname;
use function file_exists;
use function ob_get_clean;
use function ob_start;
use function unlink;

#[CoversClass(Application::class)]
#[CoversClass(RouteCacheCommand::class)]
#[CoversClass(Router::class)]
class RouteCacheCommandTest extends TestCase
{
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
        Router::Reset();
        if (file_exists($file = dirname(__FILE__) . slash(path: '/fixtures/app1/bootstrap/cache/route.php'))) {
            @unlink($file);
        }
    }

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
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws EntryNotFoundException
     * @throws Exception
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function testItCanCreateRouteCache(): void
    {
        $app = new Application(dirname(__FILE__) . slash(path: '/fixtures/app1/'));
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
     * Test it can create route cache from files.
     *
     * @return void
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws Exception
     * @throws ReflectionException
     */
    public function testItCanCreateRouteCacheFromFiles(): void
    {
        $app = new Application(dirname(__FILE__) . slash(path: '/fixtures/app1/'));
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
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws Exception
     * @throws ReflectionException
     */
    public function testItFailCreateRouteCacheFromFiles(): void
    {
        $app = new Application(dirname(__FILE__) . slash(path: '/fixtures/app1/'));
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
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws Exception
     * @throws ReflectionException
     */
    public function testItCanGenerateValidRouterCache(): void
    {
        $app = new Application(dirname(__FILE__) . slash(path: '/fixtures/app1/'));
        $app->setConfigPath();

        $command = new RouteCacheCommand([]);

        ob_start();
        $status = $command->cache($app, $this->createRouter());
        ob_get_clean();

        $cache_route = require_once dirname(__FILE__) . slash(path: '/fixtures/app1/bootstrap/cache/route.php');
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
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws Exception
     * @throws ReflectionException
     */
    public function testItCanRemoveConfigFile()
    {
        $app = new Application(dirname(__FILE__) . slash(path: '/fixtures/app1/'));
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
