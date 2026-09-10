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
use Omega\Application\ApplicationManifest;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Exceptions\Bootstrapper\HandleExceptions;
use Omega\Http\Http;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Tests\FixturesPathTrait;

use function count;

/**
 * RoadRunnerMultiRequestTest class.
 *
 * Simulates a persistent worker (e.g. RoadRunner) by driving two full
 * request/terminate cycles against the same Application instance. This is
 * the regression test for the route-re-registration bug: routes must be
 * repopulated on every request after Router::reset(), otherwise all requests
 * after the first one would fail to match (no routes registered).
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
#[CoversClass(Http::class)]
#[CoversClass(Router::class)]
final class RoadRunnerMultiRequestTest extends TestCase
{
    use FixturesPathTrait;

    /** @var Application The application instance used for kernel testing. */
    private Application $app;

    /** @var Http The HTTP service instance used for testing kernel request handling. */
    private Http $http;

    /**
     * Sets up the environment before each test method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->app = new Application($this->setFixturePath('/fixtures/application-read/'));

        $this->app->set(ApplicationManifest::class, fn () => new ApplicationManifest(
            basePath: $this->app->get('path.base'),
            applicationCachePath: $this->app->getApplicationCachePath(),
            vendorPath: '/package/'
        ));

        /**
         * Anonymous Http subclass using Router::run() as its dispatcher,
         * mirroring how a real application's kernel dispatches requests.
         */
        $this->http = new class ($this->app) extends Http {
            /**
             * Resolve the request through the static route table.
             *
             * @param Request $request Incoming HTTP request.
             * @return array<string, mixed> Dispatcher configuration.
             */
            protected function dispatcher(Request $request): array
            {
                return [
                    'callable'   => Router::run(uri: $request->getUrl(), method: $request->getMethod()),
                    'parameters' => [],
                    'middleware' => [],
                ];
            }
        };
    }

    /**
     * Tears down the environment after each test method.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->app->flush();
        HandleExceptions::resetHandlersState();
    }

    /**
     * Two consecutive request cycles against the same worker.
     *
     * Regression for the RoadRunner route bug: after the first terminate()
     * runs Router::reset(), the route table must be repopulated so the second
     * request still dispatches.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testRoutesSurviveAcrossRequests(): void
    {
        $http = $this->http;

        // Request 1
        $request  = new Request('/test');
        $response = $http->handle($request);
        $this->assertInstanceOf(Response::class, $response);
        $http->terminate($request, $response);

        // Router::reset() ran; without the fix the table is empty here.
        $this->assertGreaterThan(
            0,
            count(Router::getRoutes()),
            'Routes must be re-registered after the first request.'
        );

        // Request 2 — the regression this test guards against.
        $request2  = new Request('/test');
        $response2 = $http->handle($request2);
        $this->assertInstanceOf(
            Response::class,
            $response2,
            'Second request must produce a Response, not a route miss.'
        );
        $http->terminate($request2, $response2);
    }
}
