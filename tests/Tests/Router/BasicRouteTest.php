<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\Router\Support\TestMiddleware;
use Tests\TestCase;

use function ob_get_clean;
use function ob_start;

#[CoversClass(Router::class)]
final class BasicRouteTest extends TestCase
{
    public function testRouteCanBeRender(): void
    {
        $this->basicRegisterRoutes();

        $routeBasic  = $this->basicGetResponse('get', '/test');
        $routeNumber = $this->basicGetResponse('get', '/test/number/123');
        $routeText   = $this->basicGetResponse('get', '/test/text/xyz');
        $routeAny    = $this->basicGetResponse('get', '/test/any/xyz+123');
        $routeAll    = $this->basicGetResponse('get', '/test/any/xyz 123'); // allow all character

        $this->assertEquals('render success', $routeBasic);
        $this->assertEquals('render success, with id is - 123', $routeNumber);
        $this->assertEquals('render success, with id is - xyz', $routeText);
        $this->assertEquals('render success, with id is - xyz+123', $routeAny);
        $this->assertEquals('render success, with id is - xyz 123', $routeAll);
    }

    public function testRouteCanBeRenderUsingGroupPrefix(): void
    {
        $this->basicRegisterGroupRoutes();
        $getOne = $this->basicGetResponse('get', '/page/one');
        $getTwo = $this->basicGetResponse('get', '/page/two');

        $this->assertEquals('page one', $getOne);
        $this->assertEquals('page two', $getTwo);
    }

    public function testRouteCanBeRenderDifferentMethod(): void
    {
        $this->basicRegisterRouterDifferentMethod();

        $get     = $this->basicGetResponse('get', '/get');
        $head    = $this->basicGetResponse('head', '/head');
        $post    = $this->basicGetResponse('post', '/post');
        $put     = $this->basicGetResponse('put', '/put');
        $patch   = $this->basicGetResponse('patch', '/patch');
        $delete  = $this->basicGetResponse('delete', '/delete');
        $options = $this->basicGetResponse('options', '/options');

        $this->assertEquals('render success using get', $get);
        $this->assertEquals('render success using get over head method', $head);
        $this->assertEquals('render success using post', $post);
        $this->assertEquals('render success using put', $put);
        $this->assertEquals('render success using patch', $patch);
        $this->assertEquals('render success using delete', $delete);
        $this->assertEquals('render success using options', $options);
    }

    public function testRouteIsMethodNotAllowed(): void
    {
        $this->basicRegisterRouterMethodNotAllowed();
        $this->basicRegisterRouterNotFound();

        $get     = $this->basicGetResponse('post', '/get');
        $post    = $this->basicGetResponse('get', '/post');
        $put     = $this->basicGetResponse('get', '/put');
        $patch   = $this->basicGetResponse('get', '/patch');
        $delete  = $this->basicGetResponse('get', '/delete');
        $options = $this->basicGetResponse('get', '/options');

        $this->assertEquals('method not allowed', $get);
        $this->assertEquals('method not allowed', $post);
        $this->assertEquals('method not allowed', $put);
        $this->assertEquals('method not allowed', $patch);
        $this->assertEquals('method not allowed', $delete);
        $this->assertEquals('method not allowed', $options);
    }

    public function testPageIsNotFound(): void
    {
        $this->basicRegisterRouterNotFound();
        $page = $this->basicGetResponse('get', '/not-found');

        $this->assertEquals('page not found 404', $page);
    }

    public function testCanPassGroupMiddleware(): void
    {
        Router::middleware([TestMiddleware::class])->group(function () {
            Router::get('/', fn () => true);
        });
        $_SERVER['REQUEST_URI']    = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        Router::run();
        Router::reset();

        $this->assertEquals('oke', $_SERVER['middleware']);
    }

    public function testCanPassSingleMiddleware(): void
    {
        Router::get('/', fn () => true)->middleware([TestMiddleware::class]);
        $_SERVER['REQUEST_URI']    = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        Router::run();
        Router::reset();

        $this->assertEquals('oke', $_SERVER['middleware']);
    }

    public function testCanPassMiddlewareRunOnce(): void
    {
        TestMiddleware::$last = 0;
        Router::middleware([TestMiddleware::class])->group(function () {
            Router::get('/', fn () => true)->middleware([TestMiddleware::class]);
        });

        $_SERVER['REQUEST_URI']    = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        Router::run();
        Router::reset();

        $this->assertEquals(1, TestMiddleware::$last);

        TestMiddleware::$last = 0;
    }

    public function testRouteHasName(): void
    {
        $this->basicRegisterRoutes();

        $this->assertTrue(Router::has('route.test'));
        $this->assertFalse(Router::has('route.success'));
    }

    public function testCanUseCustomPattern(): void
    {
        Router::get('/test/custom/{custom}', function (string $custom) {
            echo 'render success, with custom is - ' . $custom;
        })
            ->name('route.test.custom')
            ->where([
                '{custom}'   => '([0-9]+)',
            ])
        ;

        $routeCustom = $this->basicGetResponse('get', '/test/custom/123');
        $this->assertEquals('render success, with custom is - 123', $routeCustom);
    }

    private function basicRegisterRoutes(): void
    {
        Router::get('/test', function () {
            echo 'render success';
        })->name('route.test');

        Router::get('/test/number/(:id)', function (string $id) {
            echo 'render success, with id is - ' . $id;
        })->name('route.test.number');

        Router::get('/test/text/(:text)', function (string $id) {
            echo 'render success, with id is - ' . $id;
        })->name('route.test.text');

        Router::get('/test/any/(:any)', function (string $id) {
            echo 'render success, with id is - ' . $id;
        })->name('route.test.any');

        Router::get('/test/any/(:all)', function (string $id) {
            echo 'render success, with id is - ' . $id;
        });
    }

    private function basicRegisterGroupRoutes(): void
    {
        Router::prefix('/page/')->group(function () {
            Router::get('one', function () {
                echo 'page one';
            });
            Router::get('two', function () {
                echo 'page two';
            });
        });
    }

    private function basicRegisterRouterDifferentMethod(): void
    {
        Router::match(['get'], '/get', function () {
            echo 'render success using get';
        })->name('name_is_get');
        Router::match(['head'], '/head', function () {
            echo 'render success using get over head method';
        });
        Router::match(['post'], '/post', function () {
            echo 'render success using post';
        });
        Router::match(['put'], '/put', function () {
            echo 'render success using put';
        });
        Router::match(['patch'], '/patch', function () {
            echo 'render success using patch';
        });
        Router::match(['delete'], '/delete', function () {
            echo 'render success using delete';
        });
        Router::match(['options'], '/options', function () {
            echo 'render success using options';
        });
    }

    private function basicRegisterRouterMethodNotAllowed(): void
    {
        Router::methodNotAllowed(function () {
            echo 'method not allowed';
        });
    }

    private function basicRegisterRouterNotFound(): void
    {
        Router::pathNotFound(function () {
            echo 'page not found 404';
        });
    }

    private function basicGetResponse(string $method, string $url): false|string
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI']    = $url;

        ob_start();
        Router::run('/');

        return ob_get_clean();
    }
}
