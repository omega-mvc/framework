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

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Tests\Router;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Router\Route;
use Omega\Router\Router;

use function ob_get_clean;
use function ob_start;

/**
 * Class NamedParameterRouteTest
 *
 * A PHPUnit test case for testing named parameter routing in the Omega Router system.
 *
 * This class focuses on verifying that routes with named parameters work correctly
 * with various parameter types, optional and refreshable parameters, and multiple
 * parameters in the same URI. It also ensures that method-not-allowed and path-not-found
 * handlers behave as expected with named parameters.
 *
 * Covered functionality includes:
 *   - Named parameters with type constraints (num, text, any)
 *   - Multiple parameters in a single route
 *   - Refreshable parameters order handling
 *   - Optional parameters
 *   - Special characters in parameters
 *   - Route naming integrity
 *
 * @category  Tests
 * @package   Router
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Route::class)]
#[CoversClass(Router::class)]
class NamedParameterRouteTest extends TestCase
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

        Router::get('/test/number/(someId:id)', function ($someId) {
            echo 'render success, with id is - ' . $someId;
        })->name('route.test.number');
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
     * @param string $url The URI to simulate.
     * @return false|string Returns false if the request is invalid, or the rendered response string.
     */
    private function getResponse(string $url): false|string
    {
        $_SERVER['REQUEST_METHOD'] = 'get';
        $_SERVER['REQUEST_URI']    = $url;

        ob_start();
        Router::run('/');

        return ob_get_clean();
    }

    /**
     * Register routes that contain multiple named parameters for testing.
     *
     * This method defines several routes with more than one named parameter in the URI,
     * each with a specific type constraint (number, text, any). These routes are used
     * to test whether the router correctly extracts multiple parameters and passes them
     * in the correct order to the route handler.
     *
     * Example URIs:
     *   - /users/123/admin
     *   - /blog/2023/05/my-awesome-post
     *   - /api/users/12/posts/34
     *
     * @return void
     */
    private function registerRouterWithMultipleParams(): void
    {
        Router::get('/users/(userId:num)/(type:text)', function ($userId, $type) {
            echo "User {$userId} is of type {$type}";
        })->name('users.type');

        Router::get('/blog/(year:num)/(month:num)/(slug:any)', function ($year, $month, $slug) {
            echo "Blog post from {$month}/{$year}: {$slug}";
        })->name('blog.post');

        Router::post('/api/users/(id:num)/posts/(postId:num)', function ($id, $postId) {
            echo "Post {$postId} for user {$id}";
        })->name('api.user.post');
    }

    /**
     * Register routes with refreshable named parameters for testing.
     *
     * This method defines routes where the order of parameters in the callback
     * may differ from the order in the URI. The router should correctly match
     * parameters by name and pass them to the handler in the proper positions.
     *
     * Example URI:
     *   - /users/123/admin
     *
     * @return void
     */
    private function registerRouterWithRefreshableParams(): void
    {
        Router::get('/users/(userId:num)/(type:text)', function ($type, $userId) {
            echo "User {$userId} is of type {$type}";
        })->name('users.type');
    }

    /**
     * Test it route can be rendered.
     *
     * @return void
     */
    public function testItRouteCanBeRender(): void
    {
        $this->registerRouter();
        $this->registerRouterMethodNotAllowed();
        $this->registerRouterNotFound();

        $routeBasic  = $this->getResponse('/test');
        $routeNumber = $this->getResponse('/test/number/123');

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
    }

    /**
     * Test it handle multiple named parameters.
     *
     * @return void
     */
    public function testItHandlesMultipleNamedParameters(): void
    {
        $this->registerRouterWithMultipleParams();

        $response = $this->getResponse('/users/123/admin');
        $this->assertEquals(
            'User 123 is of type admin',
            $response,
            'Should handle multiple named parameters correctly'
        );

        $response = $this->getResponse('/blog/2023/05/my-awesome-post');
        $this->assertEquals(
            'Blog post from 05/2023: my-awesome-post',
            $response,
            'Should handle three named parameters with different types'
        );
    }

    /**
     * Test refreshable named parameters are handled correctly.
     *
     * @return void
     */
    public function testRefreshableNamedParametersAreHandledCorrectly(): void
    {
        $this->registerRouterWithRefreshableParams();

        $response = $this->getResponse('/users/123/admin');
        $this->assertEquals(
            'User 123 is of type admin',
            $response,
            'Should handle multiple named parameters correctly'
        );
    }

    /**
     * Test it respects parameter types.
     *
     * @return void
     */
    public function testItRespectsParameterTypes(): void
    {
        Router::get('/test/(age:num)/(name:text)', function ($age, $name) {
            echo "Name: {$name}, Age: {$age}";
        });

        $response = $this->getResponse('/test/25/john');
        $this->assertEquals(
            'Name: john, Age: 25',
            $response,
            'Should match valid parameter types'
        );

        $response = $this->getResponse('/test/abc/john');
        $this->assertEquals(
            'page not found 404',
            $response,
            'Should not match invalid number type'
        );

        $response = $this->getResponse('/test/25/john123');
        $this->assertEquals(
            'page not found 404',
            $response,
            'Should not match invalid text type'
        );
    }

    /**
     * Test it handles method not allowed with named params
     *
     * @return void
     */
    public function testItHandlesMethodNotAllowedWithNamedParams(): void
    {
        Router::post('/api/users/(id:num)', function ($id) {
            echo "Create user {$id}";
        });

        $response = $this->getResponse('/api/users/123');
        $this->assertEquals(
            'method not allowed',
            $response,
            'Should return method not allowed for wrong HTTP method'
        );
    }

    /**
     * Test it handles optional parameters.
     *
     * @return void
     */
    public function testItHandlesOptionalParameters(): void
    {
        Router::get('/products', function () {
            echo 'All products';
        });

        Router::get('/products/(category:text)', function ($category) {
            echo "Category: {$category}";
        });

        Router::get('/products/(category:text)/(id:num)', function ($category, $id) {
            echo "Product {$id} in {$category}";
        });

        $response = $this->getResponse('/products');
        $this->assertEquals(
            'All products',
            $response,
            'Should work with base route'
        );

        $response = $this->getResponse('/products/electronics');
        $this->assertEquals(
            'Category: electronics',
            $response,
            'Should work with category parameter'
        );

        $response = $this->getResponse('/products/electronics/123');
        $this->assertEquals(
            'Product 123 in electronics',
            $response,
            'Should work with both category and id parameters'
        );
    }

    /**
     * Test it handles special characters in parameters.
     *
     * @return void
     */
    public function itHandlesSpecialCharactersInParameters(): void
    {
        Router::get('/search/(query:all)', function ($query) {
            echo "Searching for: {$query}";
        });

        $response = $this->getResponse('/search/php+routing+system');
        $this->assertEquals(
            'Searching for: php+routing+system',
            $response,
            'Should handle special characters in parameters'
        );
    }

    /**
     * Test it make sure router name is not overwritten.
     *
     * @return void
     */
    public function testItMakeSureRouterNameIsNotOverwritten(): void
    {
        $route = [
            'name'    => 'test.route',
            'path'    => '/test',
            'method'  => 'get',
            'handler' => function () {
                echo 'Test Route';
            },
        ];
        $routeInstance = new Route($route);
        $this->assertEquals(
            'test.route',
            $routeInstance->route()['name'],
            'Route name should not be overwritten'
        );
        $routeInstance->name('new.route');
        $this->assertEquals(
            'new.route',
            $routeInstance->route()['name'],
            'Route name should remain unchanged after setting a new name'
        );
    }

    /**
     * Test it male sure router name is not overwritten with prefix given.
     *
     * @return void
     */
    public function itMakeSureRouterNameIsNotOverwrittenWithPrefixGiven(): void
    {
        $backup        = Router::$group;
        Router::$group = ['as' => 'prefix.'];
        $route         = [
            'name'    => 'test.route',
            'path'    => '/test',
            'method'  => 'get',
            'handler' => function () {
                echo 'Test Route';
            },
        ];

        $routeInstance = new Route($route);
        $this->assertEquals(
            'prefix.test.route',
            $routeInstance['name'],
            'Route name should not be overwritten'
        );
        $routeInstance->name('new.route');
        $this->assertEquals(
            'prefix.new.route',
            $routeInstance->route()['name'],
            'Route name should remain unchanged after setting a new name'
        );

        Router::$group = $backup;
    }
}
