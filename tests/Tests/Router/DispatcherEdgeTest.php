<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\Route;
use Omega\Router\RouteDispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function call_user_func_array;

#[CoversClass(Route::class)]
#[CoversClass(RouteDispatcher::class)]
final class DispatcherEdgeTest extends TestCase
{
    public function testTreatsAUrlWithoutAPathComponentAsRoot(): void
    {
        $dispatcher = RouteDispatcher::dispatchFrom('http://localhost', 'GET', [$this->edgeRoute('/')]);

        $dispatch = $dispatcher->run(
            fn (callable $callable, array $params) => 'root',
            fn ($path) => 'not-found',
            fn ($path, $method) => 'not-allowed',
        );

        $this->assertSame('root', call_user_func_array($dispatch['callable'], $dispatch['params']));
    }

    public function testTrimsTrailingSlashesByDefault(): void
    {
        $dispatcher = RouteDispatcher::dispatchFrom('/foo/', 'GET', [$this->edgeRoute('/foo')]);

        $dispatch = $dispatcher->run(
            fn (callable $callable, array $params) => call_user_func_array($callable, $params),
            fn ($path) => 'not-found',
            fn ($path, $method) => 'not-allowed',
        );

        $this->assertSame('handler-/foo', call_user_func_array($dispatch['callable'], $dispatch['params']));
    }

    public function testKeepsTrailingSlashesWhenTrailingSlashMattersIsEnabled(): void
    {
        $dispatcher = RouteDispatcher::dispatchFrom('/foo/', 'GET', [$this->edgeRoute('/foo')])
            ->trailingSlashMatters(true);

        $dispatch = $dispatcher->run(
            fn (callable $callable, array $params) => 'found',
            fn ($path) => 'not-found',
            fn ($path, $method) => 'not-allowed',
        );

        $this->assertSame('not-found', call_user_func_array($dispatch['callable'], $dispatch['params']));
    }

    public function testMatchesCaseInsensitivelyByDefaultAndStrictlyWhenCaseMattersIsEnabled(): void
    {
        $routes = [$this->edgeRoute('/Case/Sensitive')];

        $default = RouteDispatcher::dispatchFrom('/case/sensitive', 'GET', $routes);
        $strict  = RouteDispatcher::dispatchFrom('/case/sensitive', 'GET', $routes)->caseMatters(true);

        $defaultDispatch = $default->run(
            fn (callable $callable, array $params) => 'found',
            fn ($path) => 'not-found',
            fn ($path, $method) => 'not-allowed',
        );

        $this->assertSame('found', call_user_func_array($defaultDispatch['callable'], $defaultDispatch['params']));

        $strictDispatch = $strict->run(
            fn (callable $callable, array $params) => 'found',
            fn ($path) => 'not-found',
            fn ($path, $method) => 'not-allowed',
        );

        $this->assertSame('not-found', call_user_func_array($strictDispatch['callable'], $strictDispatch['params']));
    }

    public function testCombinesBasePathWithTheRouteExpressionWhenMatching(): void
    {
        $dispatcher = RouteDispatcher::dispatchFrom('/api/users', 'GET', [$this->edgeRoute('/users')])
            ->basePath('/api');

        $dispatch = $dispatcher->run(
            fn (callable $callable, array $params) => call_user_func_array($callable, $params),
            fn ($path) => 'not-found',
            fn ($path, $method) => 'not-allowed',
        );

        $this->assertSame('handler-/users', call_user_func_array($dispatch['callable'], $dispatch['params']));
    }

    public function testReturnsTheLastMatchingRouteInMultiMatchMode(): void
    {
        $routes = [
            $this->edgeRoute('/multi'),
            $this->edgeRoute('/multi'),
        ];

        $single = RouteDispatcher::dispatchFrom('/multi', 'GET', $routes)->multiMatch(false);
        $multi  = RouteDispatcher::dispatchFrom('/multi', 'GET', $routes)->multiMatch(true);

        $singleDispatch = $single->run(
            fn (callable $callable, array $params) => call_user_func_array($callable, $params),
            fn ($path) => 'not-found',
            fn ($path, $method) => 'not-allowed',
        );

        $this->assertSame(
            'handler-/multi',
            call_user_func_array($singleDispatch['callable'], $singleDispatch['params'])
        );

        $multiDispatch = $multi->run(
            fn (callable $callable, array $params) => call_user_func_array($callable, $params),
            fn ($path) => 'not-found',
            fn ($path, $method) => 'not-allowed',
        );

        $multiResult = call_user_func_array($multiDispatch['callable'], $multiDispatch['params']);

        $this->assertSame('handler-/multi', $multiResult);
        $this->assertSame($routes[1], $multi->current());
    }

    public function testResolvesNamedParametersDuringDispatch(): void
    {
        $captured = null;

        $dispatcher = RouteDispatcher::dispatchFrom('/user/42', 'GET', [
            new Route([
                'method'     => 'GET',
                'expression' => '/user/(?P<id>\d+)',
                'function'   => fn (string $id): string => 'user-' . $id,
            ]),
        ]);

        $dispatch = $dispatcher->run(
            function (callable $callable, array $params) use (&$captured) {
                $captured = $params;

                return call_user_func_array($callable, $params);
            },
            fn ($path) => 'not-found',
            fn ($path, $method) => 'not-allowed',
        );

        $result = call_user_func_array($dispatch['callable'], $dispatch['params']);

        $this->assertSame(['id' => '42'], $captured);
        $this->assertSame('user-42', $result);
    }

    public function testAppliesPerRoutePatternsDuringDispatch(): void
    {
        $route = new Route([
            'method'     => 'GET',
            'expression' => '/post/{id}',
            'patterns'   => ['{id}' => '\d+'],
            'function'   => fn (): string => 'post',
        ]);

        $matching = RouteDispatcher::dispatchFrom('/post/123', 'GET', [$route]);
        $failing  = RouteDispatcher::dispatchFrom('/post/abc', 'GET', [$route]);

        $foundDispatch = $matching->run(
            fn (callable $callable, array $params) => 'found',
            fn ($path) => 'not-found',
            fn ($path, $method) => 'not-allowed',
        );

        $this->assertSame('found', call_user_func_array($foundDispatch['callable'], $foundDispatch['params']));

        $missingDispatch = $failing->run(
            fn (callable $callable, array $params) => 'found',
            fn ($path) => 'not-found',
            fn ($path, $method) => 'not-allowed',
        );

        $this->assertSame('not-found', call_user_func_array($missingDispatch['callable'], $missingDispatch['params']));
    }

    public function testSkipsNonMatchingMethodsAndDispatchesOnTheFirstAvailable(): void
    {
        $dispatcher = RouteDispatcher::dispatchFrom('/multi-method', 'POST', [
            new Route([
                'method'     => ['get', 'post'],
                'expression' => '/multi-method',
                'function'   => fn (): string => 'handled',
            ]),
        ]);

        $dispatch = $dispatcher->run(
            fn (callable $callable, array $params) => 'found',
            fn ($path) => 'not-found',
            fn ($path, $method) => 'not-allowed',
        );

        $this->assertSame('found', call_user_func_array($dispatch['callable'], $dispatch['params']));
    }

    public function testSetsTheCurrentRouteExpressionFromTheOriginalExpression(): void
    {
        $route      = new Route([
            'method'     => 'GET',
            'expression' => '/about',
            'function'   => fn (): string => 'about',
        ]);
        $dispatcher = RouteDispatcher::dispatchFrom('/about', 'GET', [$route]);

        $dispatcher->run(
            fn (callable $callable, array $params) => 'found',
            fn ($path) => 'not-found',
            fn ($path, $method) => 'not-allowed',
        );

        $this->assertInstanceOf(Route::class, $dispatcher->current());
        $this->assertSame('^/about$', $dispatcher->current()['expression'] ?? null);
    }

    /**
     * Build a trivial route definition.
     *
     * @param string $expression Route URI expression.
     * @param string $method     Allowed HTTP method(s).
     * @return Route
     */
    private function edgeRoute(string $expression, string $method = 'GET'): Route
    {
        return new Route([
            'method'     => $method,
            'expression' => $expression,
            'function'   => fn (): string => 'handler-' . $expression,
        ]);
    }
}
