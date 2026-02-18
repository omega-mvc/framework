<?php

/**
 * Part of Omega - Tests\Router Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Tests\Router\Attribute\TestBasicRouteAttribute;
use Tests\Router\Attribute\TestRouteAttribute;
use Tests\Router\Support\TestMiddleware;

use function array_map;
use function ob_get_clean;
use function ob_start;

/**
 * Class BasicRouteTest
 *
 * A PHPUnit test case class for testing the functionality of the Omega Router system.
 *
 * This class covers multiple aspects of routing, including:
 *   - Basic route registration and response rendering
 *   - Grouped routes with prefix handling
 *   - Routes with different HTTP methods
 *   - Handling of method-not-allowed and not-found routes
 *   - Middleware registration at global and route-specific levels
 *   - Route naming and custom pattern constraints
 *   - Attribute-based route registration
 *
 * Each private method is a helper to register routes or obtain responses for testing.
 *
 * Public test methods assert that the router behaves correctly in various scenarios.
 *
 * @category  Tests
 * @package   Router
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Router::class)]
final class BasicRouteTest extends TestCase
{
    /**
     * Register basic routes for testing.
     *
     * This method defines several GET, POST, and DELETE routes with various URI patterns,
     * including parameterized routes (number, text, any, all). Route names are assigned
     * to some routes for testing route lookup by name.
     *
     * @return void
     */
    private function registerRouter(): void
    {
        Router::get('/test', function () {
            echo 'render success';
        })->name('route.test');

        Router::get('/test/number/(:id)', function ($id) {
            echo 'render success, with id is - ' . $id;
        })->name('route.test.number');

        Router::get('/test/text/(:text)', function ($id) {
            echo 'render success, with id is - ' . $id;
        })->name('route.test.text');

        Router::get('/test/any/(:any)', function ($id) {
            echo 'render success, with id is - ' . $id;
        })->name('route.test.any');

        Router::get('/test/any/(:all)', function ($id) {
            echo 'render success, with id is - ' . $id;
        });
    }

    /**
     * Register a group of routes under a common prefix for testing.
     *
     * Demonstrates how grouped routes inherit a prefix while defining multiple child routes.
     *
     * @return void
     */
    private function registerGroupRouter(): void
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

    /**
     * Register routes with different HTTP methods.
     *
     * This method tests handling of GET, HEAD, POST, PUT, PATCH, DELETE, and OPTIONS
     * HTTP methods via the Router::match method.
     *
     * @return void
     */
    private function registerRouterDifferentMethod(): void
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

    /**
     * Register a "method not allowed" handler for testing.
     *
     * The handler will be executed when a request is made with an unsupported HTTP method
     * for a registered route.
     *
     * @return void
     */
    private function registerRouterMethodNotAllowed(): void
    {
        Router::methodNotAllowed(function () {
            echo 'method not allowed';
        });
    }

    /**
     * Register a "path not found" handler for testing.
     *
     * The handler will be executed when a request is made to a URI not matched by any route.
     *
     * @return void
     */
    private function registerRouterNotFound(): void
    {
        Router::pathNotFound(function () {
            echo 'page not found 404';
        });
    }

    /**
     * Simulate a request and capture the router output.
     *
     * This helper method sets $_SERVER['REQUEST_METHOD'] and $_SERVER['REQUEST_URI']
     * for the given request and runs the router. Output is captured via output buffering.
     *
     * @param string $method The HTTP method to simulate.
     * @param string $url The URI to simulate.
     * @return false|string Returns false if the request is invalid, or the rendered response string.
     */
    private function getResponse(string $method, string $url): false|string
    {
        $_SERVER['REQUEST_METHOD'] = $method;
        $_SERVER['REQUEST_URI']    = $url;

        ob_start();
        Router::run('/');

        return ob_get_clean();
    }

    /**
     * Test it can be rendered.
     *
     * @return void
     */
    public function testItRouteCanBeRender(): void
    {
        $this->registerRouter();

        $routeBasic  = $this->getResponse('get', '/test');
        $routeNumber = $this->getResponse('get', '/test/number/123');
        $routeText   = $this->getResponse('get', '/test/text/xyz');
        $routeAny    = $this->getResponse('get', '/test/any/xyz+123');
        $routeAll    = $this->getResponse('get', '/test/any/xyz 123'); // allow all character

        $this->assertEquals(
            'render success',
            $routeBasic,
            'the route must output same text'
        );
        $this->assertEquals(
            'render success, with id is - 123',
            $routeNumber,
            'the route must output same text'
        );
        $this->assertEquals(
            'render success, with id is - xyz',
            $routeText,
            'the route must output same text'
        );
        $this->assertEquals(
            'render success, with id is - xyz+123',
            $routeAny,
            'the route must output same text'
        );
        $this->assertEquals(
            'render success, with id is - xyz 123',
            $routeAll,
            'the route must output same text'
        );
    }

    /**
     * Test it route can be rendering using group prefix.
     *
     * @return void
     */
    public function testItRouteCanBeRenderUsingGroupPrefix(): void
    {
        $this->registerGroupRouter();
        $getOne = $this->getResponse('get', '/page/one');
        $getTwo = $this->getResponse('get', '/page/two');

        $this->assertEquals(
            'page one',
            $getOne,
            "group router with child is 'one'"
        );

        $this->assertEquals(
            'page two',
            $getTwo,
            "group router with child is 'two'"
        );
    }

    /**
     * Test it route can be rendered different method.
     *
     * @return void
     */
    public function testItRouteCanBeRenderDifferentMethod(): void
    {
        $this->registerRouterDifferentMethod();

        $get     = $this->getResponse('get', '/get');
        $head    = $this->getResponse('head', '/head');
        $post    = $this->getResponse('post', '/post');
        $put     = $this->getResponse('put', '/put');
        $patch   = $this->getResponse('patch', '/patch');
        $delete  = $this->getResponse('delete', '/delete');
        $options = $this->getResponse('options', '/options');

        $this->assertEquals(
            'render success using get',
            $get,
            'render success using get'
        );
        $this->assertEquals(
            'render success using get over head method',
            $head,
            'render success using get over head method'
        );
        $this->assertEquals(
            'render success using post',
            $post,
            'render success using post'
        );
        $this->assertEquals(
            'render success using put',
            $put,
            'render success using put'
        );
        $this->assertEquals(
            'render success using patch',
            $patch,
            'render success using patch'
        );
        $this->assertEquals(
            'render success using delete',
            $delete,
            'render success using delete'
        );
        $this->assertEquals(
            'render success using options',
            $options,
            'render success using options'
        );
    }

    /**
     * Test it route is method not allowed.
     *
     * @return void
     */
    /**public function testItRouteIsMethodNotAllowed(): void
    {
        $this->registerRouterMethodNotAllowed();

        $get     = $this->getResponse('post', '/get');
        $post    = $this->getResponse('get', '/post');
        $put     = $this->getResponse('get', '/put');
        $patch   = $this->getResponse('get', '/patch');
        $delete  = $this->getResponse('get', '/delete');
        $options = $this->getResponse('get', '/options');
        $this->assertEquals(
            'method not allowed',
            $get,
            'method not allowed'
        );
        $this->assertEquals(
            'method not allowed',
            $post,
            'method not allowed'
        );
        $this->assertEquals(
            'method not allowed',
            $put,
            'method not allowed'
        );
        $this->assertEquals(
            'method not allowed',
            $patch,
            'method not allowed'
        );
        $this->assertEquals(
            'method not allowed',
            $delete,
            'method not allowed'
        );
        $this->assertEquals(
            'method not allowed',
            $options,
            'method not allowed'
        );
    }*/
    public function testItRouteIsMethodNotAllowed(): void
    {
        // Assicurati che il fallback sia registrato PRIMA di qualsiasi getResponse()
        $this->registerRouterMethodNotAllowed();
        $this->registerRouterNotFound(); // facoltativo, ma utile per sicurezza

        // Ora chiama getResponse() per i vari metodi
        $get     = $this->getResponse('post', '/get');
        $post    = $this->getResponse('get', '/post');
        $put     = $this->getResponse('get', '/put');
        $patch   = $this->getResponse('get', '/patch');
        $delete  = $this->getResponse('get', '/delete');
        $options = $this->getResponse('get', '/options');

        $this->assertEquals('method not allowed', $get,     'method not allowed');
        $this->assertEquals('method not allowed', $post,    'method not allowed');
        $this->assertEquals('method not allowed', $put,     'method not allowed');
        $this->assertEquals('method not allowed', $patch,   'method not allowed');
        $this->assertEquals('method not allowed', $delete,  'method not allowed');
        $this->assertEquals('method not allowed', $options, 'method not allowed');
    }

    /**
     * Test it page is not found.
     *
     * @retrn void
     */
    public function testItPageIsNotFound(): void
    {
        $this->registerRouterNotFound();
        $page = $this->getResponse('get', '/not-found');

        $this->assertEquals(
            'page not found 404',
            $page,
            'it must render "page is not found"'
        );
    }

    /**
     * Test it can pass group middleware.
     *
     * @return void
     */
    public function testItCanPassGroupMiddleware(): void
    {
        //require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestMiddleware.php';

        Router::middleware([TestMiddleware::class])->group(function () {
            Router::get('/', fn () => true);
        });
        $_SERVER['REQUEST_URI']    = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        Router::run();
        Router::reset();

        $this->assertEquals('oke', $_SERVER['middleware'], 'all route must pass global middleware');
    }

    /**
     * Test it can pass single middleware.
     *
     * @return void
     */
    public function testItCanPassSingleMiddleware(): void
    {
        //require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestMiddleware.php';

        Router::get('/', fn () => true)->middleware([TestMiddleware::class]);
        $_SERVER['REQUEST_URI']    = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        Router::run();
        Router::reset();

        $this->assertEquals('oke', $_SERVER['middleware'], 'all route must pass global middleware');
    }

    /**
     * Test it can pass middleware run once.
     *
     * @return void
     */
    public function testItCanPassMiddlewareRunOnce(): void
    {
        //require_once __DIR__ . DIRECTORY_SEPARATOR . 'TestMiddleware.php';

        TestMiddleware::$last = 0;
        Router::middleware([TestMiddleware::class])->group(function () {
            Router::get('/', fn () => true)->middleware([TestMiddleware::class]);
        });

        $_SERVER['REQUEST_URI']    = '/';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        Router::run();
        Router::reset();

        $this->assertEquals(1, TestMiddleware::$last, 'all route must pass global middleware');

        TestMiddleware::$last = 0;
    }

    /**
     * Test it route has name.
     *
     * @return void
     */
    public function testItRouteHasName(): void
    {
        $this->registerRouter();

        $this->assertTrue(Router::has('route.test'));
        $this->assertFalse(Router::has('route.success'));
    }

    /**
     * Test it can use custom pattern.
     *
     * @return void
     */
    public function testItCanUseCustomPattern(): void
    {
        Router::get('/test/custom/{custom}', function ($custom) {
            echo 'render success, with custom is - ' . $custom;
        })
            ->name('route.test.custom')
            ->where([
                '{custom}'   => '([0-9]+)',
            ])
        ;

        $routeCustom = $this->getResponse('get', '/test/custom/123');
        $this->assertEquals(
            'render success, with custom is - 123',
            $routeCustom,
            'the route must output same text'
        );
    }

    /**
     * Test it can generate basic route using attribute.
     *
     * @return void
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanGenerateBasicRouteUsingAttribute(): void
    {
        Router::reset();
        Router::register(TestBasicRouteAttribute::class);

        $routes = array_map(fn ($item) => (fn () => $this->{'route'})->call($item), Router::getRoutesRaw());

        $this->assertEquals([
            [ // 0
                'method'     => ['GET'],
                'patterns'   => [],
                'uri'        => '/',
                'expression' => '/',
                'function'   => [TestBasicRouteAttribute::class, 'index'],
                'middleware' => [],
                'name'       => '',
            ],
            [ // 1
                'method'     => ['GET'],
                'patterns'   => [],
                'uri'        => '/create',
                'expression' => '/create',
                'function'   => [TestBasicRouteAttribute::class, 'create'],
                'middleware' => [],
                'name'       => '',
            ],
            [ // 2
                'method'     => ['POST'],
                'patterns'   => [],
                'uri'        => '/',
                'expression' => '/',
                'function'   => [TestBasicRouteAttribute::class, 'store'],
                'middleware' => [],
                'name'       => '',
            ],
            [ // 3
                'method'     => ['GET'],
                'patterns'   => [],
                'uri'        => '/(:id)',
                'expression' => '/(\d+)',
                'function'   => [TestBasicRouteAttribute::class, 'show'],
                'middleware' => [],
                'name'       => '',
            ],
            [ // 4
                'method'     => ['GET'],
                'patterns'   => [],
                'uri'        => '/(:id)/edit',
                'expression' => '/(\d+)/edit',
                'function'   => [TestBasicRouteAttribute::class, 'edit'],
                'middleware' => [],
                'name'       => '',
            ],
            [ // 5
                'method'     => ['put', 'patch'],
                'patterns'   => [],
                'uri'        => '/(:id)',
                'expression' => '/(\d+)',
                'function'   => [TestBasicRouteAttribute::class, 'update'],
                'middleware' => [],
                'name'       => '',
            ],
            [ // 6
                'method'     => ['DELETE'],
                'patterns'   => [],
                'uri'        => '/(:id)',
                'expression' => '/(\d+)',
                'function'   => [TestBasicRouteAttribute::class, 'destroy'],
                'middleware' => [],
                'name'       => '',
            ],
        ], $routes);
    }

    /**
     * Test it can generate route using attribute,
     *
     * @return void
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testItCanGenerateRouteUsingAttribute(): void
    {
        Router::reset();
        Router::register(TestRouteAttribute::class);

        $routes = array_map(fn ($item) => (fn () => $this->{'route'})->call($item), Router::getRoutesRaw());

        $this->assertEquals([
            [
                'method'     => ['GET'],
                'patterns'   => ['{id}' => '(\d+)'],
                'uri'        => '/test/{id}/test',
                'expression' => '/test/{id}/test',
                'function'   => [TestRouteAttribute::class, 'index'],
                'middleware' => ['testmiddeleware_class', 'testmiddeleware_method'],
                'name'       => 'test.test',
            ],
        ], $routes);
    }
}
