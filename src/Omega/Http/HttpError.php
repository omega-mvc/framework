<?php

/**
 * Part of Omega - Http Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Http;

use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Router\RouteDispatcher;
use Omega\Router\Router;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * HTTP kernel with enhanced error handling and routing resolution.
 *
 * This class extends the base HTTP kernel by integrating route
 * dispatching and advanced error handling capabilities.
 *
 * When the application runs in debug mode, it registers a
 * developer-friendly error page handler (e.g. Whoops) during
 * the application boot phase.
 *
 * It overrides the dispatcher logic to resolve routes, handle
 * "not found" and "method not allowed" scenarios, and return
 * appropriate HTTP responses.
 *
 * @category  Omega
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class HttpError extends Http
{
    /**
     * Create a new HTTP error-aware kernel instance.
     *
     * Registers a boot callback to enable detailed error pages
     * when the application is running in debug mode.
     *
     * @param Application $app Application container instance.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);

        $this->app->bootedCallback(function () {
            if ($this->app->isDebugMode() && class_exists(Run::class)) {
                /* @var PrettyPageHandler $hanlder */
                $handler = $this->app->make('error.PrettyPageHandler');
                $handler->setPageTitle('php mvc');

                /* @var Run $run */
                $run = $this->app->make('error.handle');
                $run
                    ->pushHandler($handler)
                    ->register();
            }
        });
    }

    /**
     * Resolve the request dispatcher using the routing system.
     *
     * This method dispatches the incoming request against the
     * registered routes and returns the resolved callable,
     * parameters, and middleware stack.
     *
     * It also defines fallback handlers for:
     * - Route not found (404)
     * - HTTP method not allowed (405)
     *
     * @param Request $request Incoming HTTP request.
     * @return array{
     *     callable: callable,
     *     parameters: array<string, mixed>,
     *     middleware: array<int, class-string|string>
     * } Dispatcher configuration.
     */
    protected function dispatcher(Request $request): array
    {
        $dispatcher = new RouteDispatcher($request, Router::getRoutesRaw());

        $content = $dispatcher->run(
        // found
            fn($callable, $param) => $this->app->call($callable, $param),
            // not found
            fn($path) => view('pages/404', [
                'path'    => $path,
                'headers' => ['status' => 404],
            ]),
            // method not allowed
            fn($path, $method) => view('pages/405', [
                'path'    => $path,
                'method'  => $method,
                'headers' => ['status' => 405],
            ])
        );

        return [
            'callable'   => $content['callable'],
            'parameters' => $content['params'],
            'middleware' => $content['middleware'],
        ];
    }
}
