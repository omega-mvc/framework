<?php

/**
 * Part of Omega - Tests\Http Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Middleware;

use Exception;
use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Exceptions\HttpException;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Middleware\MaintenanceMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;

/**
 * Class PreventRequestInMaintenanceTest
 *
 * This test class is responsible for verifying the behavior of the
 * MaintenanceMiddleware in various scenarios. It ensures that HTTP requests
 * are correctly handled when the application is in maintenance mode.
 *
 * The tests cover:
 *  - Preventing normal requests from proceeding.
 *  - Redirecting requests to a maintenance page.
 *  - Rendering a maintenance response and setting appropriate headers.
 *  - Throwing exceptions when a request is blocked during maintenance.
 *
 * Each test uses a dedicated Application instance and simulates HTTP
 * requests with Response objects to verify middleware behavior.
 *
 * @category  Tests
 * @package   Middleware
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(ConfigRepository::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(HttpException::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
#[CoversClass(MaintenanceMiddleware::class)]
final class PreventRequestInMaintenanceTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Test it can prevent request during maintenance.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown when resolving a binding fails.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanPreventRequestDuringMaintenance(): void
    {
        $app        = new Application($this->setFixtureBasePath());
        $config = [
            'APP_ENV'       => 'test',
            'APP_DEBUG'     => 'false',
            'STORAGE_PATH'  => $this->setFixturePath(slash(path: '/fixtures/storage/')),
        ];
        $app->loadConfig(new ConfigRepository($config));
        $middleware = new MaintenanceMiddleware($app);
        $response   = new Response('test');
        $handle     = $middleware->handle(new Request('/'), fn (Request $request) => $response);

        $this->assertEquals($handle, $response);
    }

    /**
     * Test it can redirect request during maintenance.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown when resolving a binding fails.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRedirectRequestDuringMaintenance(): void
    {
        $app        = new Application($this->setFixtureBasePath());
        $app->set('path.storage', $this->setFixturePath(slash(path:'/fixtures/application-read/storage/')));

        $middleware = new MaintenanceMiddleware($app);

        $response   = new Response('test');
        $handle     = $middleware->handle(new Request('/'), fn (Request $request) => $response);

        $this->assertEquals('/test', $handle->headers->get('Location'));
    }

    /**
     * Test it can render and retry request during maintenance.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown when resolving a binding fails.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanRenderAndRetryRequestDuringMaintenance(): void
    {
        $app        = new Application($this->setFixtureBasePath());
        $app->set('path.storage', $this->setFixturePath(slash(path:'/fixtures/application-read/storage2/')));

        $middleware = new MaintenanceMiddleware($app);
        $response   = new Response('test');
        $handle     = $middleware->handle(new Request('/'), fn (Request $request) => $response);

        $this->assertEquals('<h1>Test</h1>', $handle->getContent());
        $this->assertEquals(15, $handle->headers->get('Retry-After'));
        $this->assertEquals(503, $handle->getStatusCode());
    }

    /**
     * Test it can throws request during maintenance.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown when resolving a binding fails.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Throw when a generic error occurred.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanThrowRequestDuringMaintenance(): void
    {
        $app        = new Application($this->setFixtureBasePath());
        $app->set('path.storage', $this->setFixturePath(slash(path:'/fixtures/application-read/storage3/')));

        $middleware = new MaintenanceMiddleware($app);
        $response   = new Response('test');

        $this->expectException(HttpException::class);
        $middleware->handle(new Request('/'), fn (Request $request) => $response);
    }
}
