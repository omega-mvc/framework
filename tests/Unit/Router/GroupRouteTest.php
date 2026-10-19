<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Http\Request;
use Omega\Router\RouteDispatcher;
use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Router\Support\SomeClass;
use Tests\TestCase;

use function Tests\Router\Support\dispatcher;

require_once __DIR__ . '/Support/Dispatcher.php';

#[CoversClass(Request::class)]
#[CoversClass(RouteDispatcher::class)]
#[CoversClass(Router::class)]
final class GroupRouteTest extends TestCase
{
    protected function tearDown(): void
    {
        Router::reset();

        parent::tearDown();
    }

    public function testCanMakeCustomRouteGroup(): void
    {
        Router::group([
            'prefix' => '/test',
        ], function () {
            Router::get('/foo', [SomeClass::class, 'foo']);
        });
        Router::get('/bar', [SomeClass::class, 'bar']);

        $res = dispatcher('/test/foo', 'get');
        $this->assertEquals('bar', $res);

        $res = dispatcher('/bar', 'get');
        $this->assertEquals('foo', $res);
    }

    public function testCanHandleNestedPrefixes(): void
    {
        Router::prefix('/api')->group(function () {
            Router::get('/status', function () {
                echo 'api-status';
            });

            Router::prefix('/v1')->group(function () {
                Router::get('/users', function () {
                    echo 'api-v1-users';
                });

                Router::prefix('/admin')->group(function () {
                    Router::get('/dashboard', function () {
                        echo 'api-v1-admin-dashboard';
                    });
                });
            });
        });

        $res = dispatcher('/api/status', 'get');
        $this->assertEquals('api-status', $res);

        $res = dispatcher('/api/v1/users', 'get');
        $this->assertEquals('api-v1-users', $res);

        $res = dispatcher('/api/v1/admin/dashboard', 'get');
        $this->assertEquals('api-v1-admin-dashboard', $res);
    }
}
