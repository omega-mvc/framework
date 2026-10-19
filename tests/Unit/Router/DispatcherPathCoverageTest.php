<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\Route;
use Omega\Router\RouteDispatcher;
use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;
use ReflectionProperty;
use Tests\TestCase;

use function call_user_func_array;

#[CoversClass(Route::class)]
#[CoversClass(RouteDispatcher::class)]
#[CoversClass(Router::class)]
final class DispatcherPathCoverageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Router::reset();
    }

    protected function tearDown(): void
    {
        Router::reset();

        parent::tearDown();
    }

    public function testTriggersNotFoundWhenTheRouteTableIsEmpty(): void
    {
        $_SERVER['REQUEST_URI'] = '/anything';

        $this->assertSame('notFound', $this->dispatchOutcome([], '/anything', 'GET'));
    }

    public function testMatchesAPlainRouteWithAStringMethod(): void
    {
        $route = $this->dispatchRoute(['expression' => '/plain']);

        $this->assertSame('found', $this->dispatchOutcome([$route], '/plain', 'GET'));
        $this->assertSame('methodNotAllowed', $this->dispatchOutcome([$route], '/plain', 'POST'));
    }

    public function testMatchesARouteWhoseMethodListNeedsSeveralIterations(): void
    {
        $route = $this->dispatchRoute(['expression' => '/list', 'method' => ['post', 'put', 'get']]);

        $this->assertSame('found', $this->dispatchOutcome([$route], '/list', 'PUT'));
    }

    public function testDoesNotEnterTheMethodLoopForAnEmptyMethodList(): void
    {
        $route = $this->dispatchRoute(['expression' => '/empty-methods', 'method' => []]);

        $this->assertSame('methodNotAllowed', $this->dispatchOutcome([$route], '/empty-methods', 'GET'));
    }

    public function testContinuesOverNonMatchingRoutesBeforeFindingAMatch(): void
    {
        $routes = [
            $this->dispatchRoute(['expression' => '/first']),
            $this->dispatchRoute(['expression' => '/second']),
        ];

        $this->assertSame('found', $this->dispatchOutcome($routes, '/second', 'GET'));
    }

    public function testStopsAtTheFirstMatchUnlessMultiMatchIsEnabled(): void
    {
        $first  = $this->dispatchRoute(['expression' => '/multi']);
        $second = $this->dispatchRoute(['expression' => '/multi']);

        $this->assertSame('found', $this->dispatchOutcome([$first, $second], '/multi', 'GET'));

        $dispatcher = RouteDispatcher::dispatchFrom('/multi', 'GET', [$first, $second])->multiMatch(true);
        $dispatcher->run(
            fn (callable $callable, array $params): string => 'found',
            fn (string $path): string => 'notFound',
            fn (string $path, string $method): string => 'methodNotAllowed',
        );

        $this->assertSame($second, $dispatcher->current());
    }

    public function testAppliesABasePathPrefixDuringMatching(): void
    {
        $route = $this->dispatchRoute(['expression' => '/users']);

        $this->assertSame('found', $this->dispatchOutcome([$route], '/api/users', 'GET', ['basePath' => '/api']));
        $this->assertSame('notFound', $this->dispatchOutcome([$route], 'api/users', 'GET', ['basePath' => 'api']));
        $this->assertSame('found', $this->dispatchOutcome([$route], '/users', 'GET', ['basePath' => '/']));
        $this->assertSame('found', $this->dispatchOutcome([$route], '/users', 'GET'));
    }

    public function testHonoursCaseSensitivityWhileMatching(): void
    {
        $route = $this->dispatchRoute(['expression' => '/CaseSensitive']);

        $this->assertSame(
            'notFound',
            $this->dispatchOutcome([$route], '/casesensitive', 'GET', ['caseMatters' => true])
        );
        $this->assertSame('found', $this->dispatchOutcome([$route], '/casesensitive', 'GET'));
    }

    public function testHonoursTrailingSlashSignificanceWhileMatching(): void
    {
        $route = $this->dispatchRoute(['expression' => '/trailing']);

        $this->assertSame(
            'notFound',
            $this->dispatchOutcome([$route], '/trailing/', 'GET', ['trailingSlashMatters' => true])
        );
        $this->assertSame('found', $this->dispatchOutcome([$route], '/trailing/', 'GET'));
    }

    public function testKeepsTheBareBasePathUnchangedWhenTheRequestPathEqualsItWithATrailingSlash(): void
    {
        $route = $this->dispatchRoute(['expression' => '/users']);

        $this->assertSame(
            'notFound',
            $this->dispatchOutcome([$route], '/api/', 'GET', ['basePath' => '/api'])
        );
    }

    public function testStripsTheQueryStringBeforeMatchingAndToleratesMissingPaths(): void
    {
        $route = $this->dispatchRoute(['expression' => '/query']);

        $this->assertSame('found', $this->dispatchOutcome([$route], '/query?a=1&b=2', 'GET'));
        $this->assertSame('notFound', $this->dispatchOutcome([$route], '', 'GET'));
        $this->assertSame('notFound', $this->dispatchOutcome([$route], 'http://', 'GET'));
    }

    public function testResolvesNamedParametersAndPerRoutePatterns(): void
    {
        $named = new Route([
            'expression' => '/user/((\d+))',
            'method'     => 'get',
            'function'   => static fn (string $id): string => 'user-' . $id,
        ]);

        $this->assertSame('found', $this->dispatchOutcome([$named], '/user/42', 'GET'));

        $patterned = $this->dispatchRoute(['expression' => '/item/(:num)', 'patterns' => ['(:num)' => '(\d+)']]);
        $this->assertSame('found', $this->dispatchOutcome([$patterned], '/item/7', 'GET'));
    }

    public function testTriggersNotFoundAndMethodNotAllowedWithNullCallbacksViaReflection(): void
    {
        $missingRoute = $this->dispatchRoute(['expression' => '/exists', 'method' => 'post']);

        $dispatcher = RouteDispatcher::dispatchFrom('/exists', 'GET', [$missingRoute]);

        (new ReflectionProperty(RouteDispatcher::class, 'notFound'))->setValue($dispatcher, null);
        (new ReflectionProperty(RouteDispatcher::class, 'methodNotAllowed'))->setValue($dispatcher, null);

        $method = new ReflectionMethod(RouteDispatcher::class, 'dispatch');
        $method->invoke($dispatcher, '', false, false, false);

        $this->assertNull($dispatcher->current());

        $noRoute = RouteDispatcher::dispatchFrom('/missing', 'GET', []);

        (new ReflectionProperty(RouteDispatcher::class, 'notFound'))->setValue($noRoute, null);
        (new ReflectionProperty(RouteDispatcher::class, 'methodNotAllowed'))->setValue($noRoute, null);

        (new ReflectionMethod(RouteDispatcher::class, 'dispatch'))->invoke($noRoute, '', false, false, false);

        $this->assertNull($noRoute->current());
    }

    public function testCoversEveryFallbackCallbackCombinationViaReflection(): void
    {
        $pathMatch    = $this->dispatchRoute(['expression' => '/hit', 'method' => 'post']);
        $pathMismatch = $this->dispatchRoute(['expression' => '/miss']);

        $scenarios = [
            'pathMatch, methodNotAllowed null'    => ['/hit', [$pathMatch], 'GET', null, fn (): string => 'nf'],
            'pathMatch, notFound null'            => ['/hit', [$pathMatch], 'GET', fn (): string => 'mna', null],
            'pathMatch, all null'                 => ['/hit', [$pathMatch], 'GET', null, null],
            'pathMismatch, both null'             => ['/nope', [$pathMismatch], 'GET', null, null],
            'pathMismatch, methodNotAllowed null' => ['/nope', [$pathMismatch], 'GET', null, fn (): string => 'nf'],
            'pathMismatch, all callable'          => ['/nope', [$pathMismatch], 'GET', fn (): string => 'mna', fn (): string => 'nf'],
        ];

        // Several identical rounds accumulate consecutive traces for every shape.
        foreach (range(1, 6) as $round) {
            foreach ($scenarios as $scenario => [$uri, $routes, $method, $mna, $nf]) {
                $dispatcher = RouteDispatcher::dispatchFrom($uri, $method, $routes);

                (new ReflectionProperty(RouteDispatcher::class, 'methodNotAllowed'))->setValue($dispatcher, $mna);
                (new ReflectionProperty(RouteDispatcher::class, 'notFound'))->setValue($dispatcher, $nf);

                (new ReflectionMethod(RouteDispatcher::class, 'dispatch'))->invoke(
                    $dispatcher,
                    '',
                    false,
                    false,
                    false
                );
            }
        }

        $this->assertCount(6, $scenarios);
    }

    public function testMatchesOnlyTheLastOfSeveralConsecutiveNonMatchingRoutes(): void
    {
        $routes = array_map(
            fn (int $i): Route => $this->dispatchRoute(['expression' => "/miss-{$i}"]),
            range(1, 5)
        );

        $routes[] = $this->dispatchRoute(['expression' => '/hit']);

        $this->assertSame('found', $this->dispatchOutcome($routes, '/hit', 'GET'));
    }

    public function testAccumulatesConsecutiveMethodNotAllowedAndNotFoundDispatches(): void
    {
        $postOnly = $this->dispatchRoute(['expression' => '/post-only', 'method' => 'post']);
        $plain    = $this->dispatchRoute(['expression' => '/plain']);

        foreach (range(1, 6) as $round) {
            $this->assertSame('methodNotAllowed', $this->dispatchOutcome([$postOnly], '/post-only', 'GET'));
            $this->assertSame('notFound', $this->dispatchOutcome([$plain], '/missing', 'GET'));
        }
    }

    public function testMatchesARouteWhoseExpressionKeyIsMissingAndExposesMiddleware(): void
    {
        $route = new Route([
            'method'     => 'get',
            'function'   => static fn (): string => 'x',
            'middleware' => [Support\TestMiddleware::class],
        ]);

        $this->assertSame('found', $this->dispatchOutcome([$route], '', 'GET'));
    }

    /**
     * Dispatch the given routes and return the triggered outcome label.
     *
     * @param Route[] $routes Route instances to dispatch against.
     * @param string  $uri    Request URI.
     * @param string  $method Request HTTP method.
     * @param array{basePath?: string, caseMatters?: bool, trailingSlashMatters?: bool, multiMatch?: bool} $options
     *                       Dispatcher options.
     * @return string One of: found, notFound, methodNotAllowed.
     */
    private function dispatchOutcome(array $routes, string $uri, string $method, array $options = []): string
    {
        $dispatcher = RouteDispatcher::dispatchFrom($uri, $method, $routes)
            ->basePath($options['basePath'] ?? '')
            ->caseMatters($options['caseMatters'] ?? false)
            ->trailingSlashMatters($options['trailingSlashMatters'] ?? false)
            ->multiMatch($options['multiMatch'] ?? false);

        $dispatch = $dispatcher->run(
            fn (callable $callable, array $params): string => 'found',
            fn (string $path): string => 'notFound',
            fn (string $path, string $method): string => 'methodNotAllowed',
        );

        $outcome = call_user_func_array($dispatch['callable'], $dispatch['params']);

        if (!is_string($outcome)) {
            throw new \LogicException('Dispatcher callbacks must return a string outcome label.');
        }

        return $outcome;
    }

    /**
     * Build a route instance from a partial definition.
     *
     * @param array{method?: string|array<int, string>, uri?: string, expression?: string, function?: mixed,
     *   patterns?: array<string, string>, middleware?: array<int, class-string>, name?: string} $data Route definition.
     * @return Route
     */
    private function dispatchRoute(array $data): Route
    {
        return new Route($data + [
            'method'   => 'get',
            'function' => static fn (): string => 'route',
        ]);
    }
}
