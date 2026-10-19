<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\AbstractRouter;
use Omega\Router\RouteDispatcher;
use Omega\Router\RouteGroup;
use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Router\Support\NoHandleMiddleware;
use Tests\Router\Support\TestMiddleware;
use Tests\TestCase;

#[CoversClass(AbstractRouter::class)]
#[CoversClass(RouteDispatcher::class)]
#[CoversClass(RouteGroup::class)]
#[CoversClass(Router::class)]
final class RouterRunTest extends TestCase
{
    protected function tearDown(): void
    {
        Router::reset();

        parent::tearDown();
    }

    public function testRunFallsBackToServerDefaultsWhenUriAndMethodAreOmitted(): void
    {
        Router::get('/', fn () => 'root');

        unset($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);

        $this->assertSame('root', Router::run());
    }

    public function testRunAcceptsExplicitUriAndMethodArguments(): void
    {
        Router::post('/explicit', fn () => 'posted');

        $this->assertSame('posted', Router::run(uri: '/explicit', method: 'POST'));
    }

    public function testRunFallsBackToTheServerMethodWhenOnlyTheUriIsPassed(): void
    {
        Router::get('/server-method', fn () => 'served');

        $_SERVER['REQUEST_METHOD'] = 'GET';

        $this->assertSame('served', Router::run(uri: '/server-method'));
    }

    public function testRunReturnsNullWhenNoRouteMatchesAndNoNotFoundHandlerIsSet(): void
    {
        Router::get('/only', fn () => 'ok');

        $this->assertNull(Router::run(uri: '/missing', method: 'GET'));
    }

    public function testRunReturnsNullWhenTheMethodIsNotAllowedAndNoHandlerIsSet(): void
    {
        Router::get('/only', fn () => 'ok');

        $this->assertNull(Router::run(uri: '/only', method: 'POST'));
    }

    public function testRunMatchesARouteAgainstANonEmptyBasePath(): void
    {
        Router::get('/users', fn () => 'users');

        $this->assertSame('users', Router::run(basePath: '/api', uri: '/api/users', method: 'GET'));
    }

    public function testRunHonoursCaseSensitiveMatching(): void
    {
        Router::get('/CaseRoute', fn () => 'case-matched');

        $this->assertSame('case-matched', Router::run(caseMatters: true, uri: '/CaseRoute', method: 'GET'));
        $this->assertNull(Router::run(caseMatters: true, uri: '/caseroute', method: 'GET'));
        $this->assertSame('case-matched', Router::run(caseMatters: false, uri: '/caseroute', method: 'GET'));
    }

    public function testRunPicksTheFirstMatchByDefaultAndTheLastMatchInMultiMatchMode(): void
    {
        Router::get('/multi', fn () => 'one');
        Router::get('/multi', fn () => 'two');

        $this->assertSame('one', Router::run(multiMatch: false, uri: '/multi', method: 'GET'));
        $this->assertSame('two', Router::run(multiMatch: true, uri: '/multi', method: 'GET'));
    }

    public function testRunInstantiatesMiddlewareThatDoesNotExposeAHandleMethod(): void
    {
        Router::get('/mw', fn () => 'mw')->middleware([NoHandleMiddleware::class]);

        $this->assertSame('mw', Router::run(uri: '/mw', method: 'GET'));
    }

    public function testRunExecutesDuplicatedMiddlewareOnlyOncePerRequest(): void
    {
        TestMiddleware::$last = 0;

        Router::middleware([TestMiddleware::class])->group(function (): void {
            Router::get('/dup', fn () => 'dup')->middleware([TestMiddleware::class]);
        });

        Router::run(uri: '/dup', method: 'GET');

        $this->assertSame(1, TestMiddleware::$last);

        TestMiddleware::$last = 0;
    }
}
