<?php

declare(strict_types=1);

namespace Tests\Http;

use Closure;
use Exception;
use Omega\Application\Application;
use Omega\Application\ApplicationInterface;
use Omega\Application\ApplicationManifest;
use Omega\Database\DatabaseManager;
use Omega\Exceptions\Bootstrapper\HandleExceptions;
use Omega\Exceptions\ExceptionHandler;
use Omega\Http\Http;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Http\Support\TerminateHookMiddleware;
use Tests\TestCase;

use function is_string;

#[CoversClass(Http::class)]
final class HttpKernelCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        HandleExceptions::resetHandlersState();

        $this->app = new Application(__DIR__ . '/fixtures/application-read/');

        $this->app->set(ApplicationManifest::class, fn () => new ApplicationManifest(
            basePath: is_string($path = $this->app->get('path.base')) ? $path : '',
            applicationCachePath: $this->app->getApplicationCachePath(),
            vendorPath: '/package/'
        ));
    }

    protected function tearDown(): void
    {
        if (isset($this->app)) {
            $this->app->flush();
        }

        HandleExceptions::resetHandlersState();

        parent::tearDown();
    }

    public function testHandleFiltersDispatcherParameters(): void
    {
        $http = new class ($this->app) extends Http {
            protected array $middleware = [];

            protected function dispatcher(Request $request): array
            {
                return [
                    'callable'   => static fn (mixed ...$arguments): Response => new Response('filtered'),
                    'parameters' => [
                        'text'    => 'value',
                        'number'  => 7,
                        'enabled' => true,
                        'ignored' => ['x'],
                        'null'    => null,
                    ],
                    'middleware' => [],
                ];
            }
        };

        $request  = new Request('/test');
        $response = $http->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('filtered', $response->getContent());
        $this->assertSame('value', $request->getAttribute('text', ''));
        $this->assertSame(7, $request->getAttribute('number', 0));
        $this->assertTrue($request->getAttribute('enabled', false));
        $this->assertSame('', $request->getAttribute('ignored', ''));
        $this->assertSame('', $request->getAttribute('null', ''));
    }

    public function testHandleRejectsInvalidExceptionHandler(): void
    {
        $this->app->set(ExceptionHandler::class, new \stdClass());

        $http = new class ($this->app) extends Http {
            protected function dispatcher(Request $request): array
            {
                throw new Exception('boom');
            }
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('The exception handler must be an instance of ' . ExceptionHandler::class);

        $http->handle(new Request('/test'));
    }

    public function testResetForRequestResetsResolvedDatabaseManager(): void
    {
        $manager = $this->createMock(DatabaseManager::class);
        $manager->expects($this->once())->method('resetConnectionsForRequest');

        $this->app->set(DatabaseManager::class, $manager);

        $http = new class ($this->app) extends Http {
            public function reset(): void
            {
                $this->resetForRequest();
            }
        };

        $http->reset();

        $this->assertTrue($this->app->resolved(DatabaseManager::class));
    }

    public function testResetForRequestSkipsMissingApplicationHookWithResolvedDatabaseManager(): void
    {
        $manager = $this->createMock(DatabaseManager::class);
        $manager->expects($this->once())->method('resetConnectionsForRequest');

        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())->method('resolved')->with(DatabaseManager::class)->willReturn(true);
        $app->expects($this->once())->method('get')->with(DatabaseManager::class)->willReturn($manager);
        $app->expects($this->once())->method('terminate');

        $http = new class ($app) extends Http {
            protected array $middleware = [];

            protected function dispatcherMiddleware(Request $request): ?array
            {
                return null;
            }
        };

        $http->terminate(new Request('/test'), new Response('ok'));
    }

    public function testResetForRequestIgnoresResolvedNonManagerBinding(): void
    {
        $this->app->set(DatabaseManager::class, new \stdClass());

        $http = new class ($this->app) extends Http {
            public function reset(): void
            {
                $this->resetForRequest();
            }
        };

        $http->reset();

        $this->assertTrue($this->app->resolved(DatabaseManager::class));
    }

    public function testResetForRequestIgnoresNonManagerBindingWithoutApplicationHook(): void
    {
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())->method('resolved')->with(DatabaseManager::class)->willReturn(true);
        $app->expects($this->once())->method('get')->with(DatabaseManager::class)->willReturn(new \stdClass());
        $app->expects($this->once())->method('terminate');

        $http = new class ($app) extends Http {
            protected array $middleware = [];

            protected function dispatcherMiddleware(Request $request): ?array
            {
                return null;
            }
        };

        $http->terminate(new Request('/test'), new Response('ok'));
    }

    public function testTerminateHandlesEmptyMiddlewareAndApplicationWithoutResetHook(): void
    {
        $app = $this->createMock(ApplicationInterface::class);
        $app->expects($this->once())->method('terminate');
        $app->expects($this->once())->method('resolved')->with(DatabaseManager::class)->willReturn(false);

        $http = new class ($app) extends Http {
            protected array $middleware = [];

            protected function dispatcherMiddleware(Request $request): ?array
            {
                return null;
            }
        };

        $http->terminate(new Request('/test'), new Response('ok'));
    }

    public function testTerminateExecutesMiddlewareTerminateHooks(): void
    {
        TerminateHookMiddleware::$terminated = false;

        $http = new class ($this->app) extends Http {
            protected array $middleware = [];

            /**
             * @return array<int, class-string>
             */
            protected function dispatcherMiddleware(Request $request): array
            {
                return [TerminateHookMiddleware::class];
            }
        };

        $http->terminate(new Request('/test'), new Response('ok'));

        $this->assertTrue(TerminateHookMiddleware::$terminated);
    }

    public function testResponseTypeWrapsStringContent(): void
    {
        $response = ($this->pipelineFor('plain'))(new Request('/test'));

        $this->assertSame('plain', $response->getContent());
    }

    public function testResponseTypeWrapsArrayContent(): void
    {
        $response = ($this->pipelineFor(['a' => 'b']))(new Request('/test'));

        $this->assertSame(['a' => 'b'], $response->getContent());
    }

    public function testResponseTypeRejectsUnsupportedContent(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Content must return as response|string|array');

        ($this->pipelineFor(123))(new Request('/test'));
    }

    public function testDispatcherReturnsDefaultConfiguration(): void
    {
        $http = new class ($this->app) extends Http {
            /**
             * @return array<string, mixed>
             */
            public function config(Request $request): array
            {
                return parent::dispatcher($request);
            }
        };

        $config = $http->config(new Request('/test'));

        $this->assertInstanceOf(Response::class, $config['callable']);
        $this->assertSame([], $config['parameters']);
        $this->assertSame([], $config['middleware']);
    }

    public function testDispatcherMiddlewareReturnsNullWithoutCurrentRoute(): void
    {
        Router::reset();

        $http = new class ($this->app) extends Http {
            /**
             * @return array<int, mixed>|null
             */
            public function middleware(Request $request): ?array
            {
                return parent::dispatcherMiddleware($request);
            }
        };

        $this->assertNull($http->middleware(new Request('/test')));
    }

    public function testExecuteMiddlewareRejectsNonResponseResult(): void
    {
        $middleware = new class {
            public function handle(Request $request, callable $next): string
            {
                return 'not-a-response';
            }
        };

        $http = new class ($this->app) extends Http {
            public function run(object $middleware, Request $request, callable $next): Response
            {
                return $this->executeMiddleware($middleware, $request, $next);
            }
        };

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Middleware must return a ' . Response::class . ' instance');

        $http->run($middleware, new Request('/test'), static fn (): Response => new Response('next'));
    }

    /**
     * @return Closure(Request): Response
     */
    private function pipelineFor(mixed $content): Closure
    {
        $dispatcher = [
            'callable'   => static fn () => $content,
            'parameters' => [],
            'middleware' => [],
        ];

        return (fn (): Closure => $this->middlewarePipeline([], $dispatcher))->call(new Http($this->app));
    }
}
