<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\Route;
use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function ob_get_clean;
use function ob_start;

#[CoversClass(Route::class)]
#[CoversClass(Router::class)]
final class NamedParameterRouteTest extends TestCase
{
    public function testRouteCanBeRender(): void
    {
        $this->namedRegisterRouter();
        $this->namedRegisterRouterMethodNotAllowed();
        $this->namedRegisterRouterNotFound();

        $routeBasic  = $this->namedGetResponse('/test');
        $routeNumber = $this->namedGetResponse('/test/number/123');

        $this->assertEquals('render success', $routeBasic);
        $this->assertEquals('render success, with id is - 123', $routeNumber);
    }

    public function testHandlesMultipleNamedParameters(): void
    {
        $this->namedRegisterRouterWithMultipleParams();

        $response = $this->namedGetResponse('/users/123/admin');
        $this->assertEquals('User 123 is of type admin', $response);

        $response = $this->namedGetResponse('/blog/2023/05/my-awesome-post');
        $this->assertEquals('Blog post from 05/2023: my-awesome-post', $response);
    }

    public function testRefreshableNamedParametersAreHandledCorrectly(): void
    {
        $this->namedRegisterRouterWithRefreshableParams();

        $response = $this->namedGetResponse('/users/123/admin');
        $this->assertEquals('User 123 is of type admin', $response);
    }

    public function testRespectsParameterTypes(): void
    {
        Router::get('/test/(age:num)/(name:text)', function (string $age, string $name) {
            echo "Name: {$name}, Age: {$age}";
        });

        $response = $this->namedGetResponse('/test/25/john');
        $this->assertEquals('Name: john, Age: 25', $response);

        $response = $this->namedGetResponse('/test/abc/john');
        $this->assertEquals('page not found 404', $response);

        $response = $this->namedGetResponse('/test/25/john123');
        $this->assertEquals('page not found 404', $response);
    }

    public function testHandlesMethodNotAllowedWithNamedParams(): void
    {
        Router::post('/api/users/(id:num)', function (string $id) {
            echo "Create user {$id}";
        });

        $response = $this->namedGetResponse('/api/users/123');
        $this->assertEquals('method not allowed', $response);
    }

    public function testHandlesOptionalParameters(): void
    {
        Router::get('/products', function () {
            echo 'All products';
        });

        Router::get('/products/(category:text)', function (string $category) {
            echo "Category: {$category}";
        });

        Router::get('/products/(category:text)/(id:num)', function (string $category, string $id) {
            echo "Product {$id} in {$category}";
        });

        $response = $this->namedGetResponse('/products');
        $this->assertEquals('All products', $response);

        $response = $this->namedGetResponse('/products/electronics');
        $this->assertEquals('Category: electronics', $response);

        $response = $this->namedGetResponse('/products/electronics/123');
        $this->assertEquals('Product 123 in electronics', $response);
    }

    public function testHandlesSpecialCharactersInParameters(): void
    {
        Router::get('/search/(query:all)', function (string $query) {
            echo "Searching for: {$query}";
        });

        $response = $this->namedGetResponse('/search/php+routing+system');
        $this->assertEquals('Searching for: php+routing+system', $response);
    }

    public function testMakeSureRouterNameIsNotOverwritten(): void
    {
        $route = [
            'name'    => 'test.route',
            'uri'     => '/test',
            'method'  => 'get',
            'function' => function () {
                echo 'Test Route';
            },
        ];
        $routeInstance = new Route($route);
        $this->assertEquals('test.route', $routeInstance->route()['name']);
        $routeInstance->name('new.route');
        $this->assertEquals('new.route', $routeInstance->route()['name']);
    }

    public function testMakeSureRouterNameIsNotOverwrittenWithPrefixGiven(): void
    {
        $backup        = Router::$group;
        Router::$group = ['prefix' => '', 'middleware' => [], 'as' => 'prefix.'];
        $route         = [
            'name'    => 'test.route',
            'uri'     => '/test',
            'method'  => 'get',
            'function' => function () {
                echo 'Test Route';
            },
        ];

        $routeInstance = new Route($route);
        $this->assertEquals('prefix.test.route', $routeInstance['name']);
        $routeInstance->name('new.route');
        $this->assertEquals('prefix.new.route', $routeInstance->route()['name']);

        Router::$group = $backup;
    }

    private function namedRegisterRouter(): void
    {
        Router::get('/test', function () {
            echo 'render success';
        })->name('route.test');

        Router::get('/test/number/(someId:id)', function (string $someId) {
            echo 'render success, with id is - ' . $someId;
        })->name('route.test.number');
    }

    private function namedRegisterRouterMethodNotAllowed(): void
    {
        Router::methodNotAllowed(function () {
            echo 'method not allowed';
        });
    }

    private function namedRegisterRouterNotFound(): void
    {
        Router::pathNotFound(function () {
            echo 'page not found 404';
        });
    }

    private function namedGetResponse(string $url): false|string
    {
        $_SERVER['REQUEST_METHOD'] = 'get';
        $_SERVER['REQUEST_URI']    = $url;

        ob_start();
        Router::run('/');

        return ob_get_clean();
    }

    private function namedRegisterRouterWithMultipleParams(): void
    {
        Router::get('/users/(userId:num)/(type:text)', function (string $userId, string $type) {
            echo "User {$userId} is of type {$type}";
        })->name('users.type');

        Router::get('/blog/(year:num)/(month:num)/(slug:any)', function (string $year, string $month, string $slug) {
            echo "Blog post from {$month}/{$year}: {$slug}";
        })->name('blog.post');

        Router::post('/api/users/(id:num)/posts/(postId:num)', function (string $id, string $postId) {
            echo "Post {$postId} for user {$id}";
        })->name('api.user.post');
    }

    private function namedRegisterRouterWithRefreshableParams(): void
    {
        Router::get('/users/(userId:num)/(type:text)', function (string $type, string $userId) {
            echo "User {$userId} is of type {$type}";
        })->name('users.type');
    }
}
