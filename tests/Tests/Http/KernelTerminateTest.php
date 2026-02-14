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

namespace Tests\Http;

use Exception;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Http;
use Omega\Http\Request;
use Omega\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;
use Tests\Http\Support\TestKernelTerminate;

use function ob_get_clean;
use function ob_start;

/**
 * KernelTerminateTest class.
 *
 * Tests the kernel's ability to register and execute terminating callbacks.
 * This includes verifying the interaction between the HTTP service and the
 * application's terminate mechanism.
 *
 * @category  Tests
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Http::class)]
#[CoversClass(Request::class)]
#[CoversClass(Response::class)]
final class KernelTerminateTest extends TestCase
{
    use FixturesPathTrait;

    /** @var Application The application instance used for kernel testing. */
    private Application $app;

    /** @var Http The HTTP service instance used for testing kernel request handling. */
    private Http $http;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws Exception Throw if a generic error occurred.
     */
    protected function setUp(): void
    {
        $this->app = new Application($this->setFixtureBasePath());

        $this->app->set(
            Http::class,
            fn () => new $this->http($this->app)
        );

        /**
         * Anonymous Http subclass used in this test to override middleware behavior.
         */
        $this->http = new class($this->app) extends Http {

            /**
             * Handles an incoming HTTP request.
             *
             * @param Request $request The HTTP request to handle.
             * @return Response The HTTP response returned after handling the request.
             */
            public function handle(Request $request): Response
            {
                return new Response('ok');
            }

            /**
             * Returns the middleware dispatcher for the request.
             *
             * @param Request $request The HTTP request for which to return middleware.
             * @return array|null An array of middleware class names to execute, or null if none.
             */
            protected function dispatcherMiddleware(Request $request): ?array
            {
                return [TestKernelTerminate::class];
            }
        };
    }

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
        $this->app->flush();
    }

    /**
     * Test it can terminate.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanTerminate(): void
    {
        $http        = $this->app->make(Http::class);
        $response    = $http->handle(
            $request = new Request('/test')
        );

        $this->app->registerTerminate(static function () {
            echo 'terminated.';
        });

        ob_start();
        $http->terminate($request, $response);
        $out = ob_get_clean();

        $this->assertEquals('/testokterminated.', $out);
    }
}

