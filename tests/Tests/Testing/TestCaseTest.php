<?php

/**
 * Part of Omega - Tests\Testing Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpExpressionResultUnusedInspection */
/** @noinspection PhpConditionAlreadyCheckedInspection */

declare(strict_types=1);

namespace Tests\Testing;

use Exception;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Http;
use Omega\Http\Response;
use Omega\Testing\TestCase;
use Omega\Testing\TestJsonResponse;
use Omega\Testing\TestResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerExceptionInterface;
use ReflectionClass;
use ReflectionException;
use Throwable;

use function dirname;

/**
 * TestCaseTest
 *
 * Ensures that the base testing infrastructure provided by Omega's
 * custom TestCase class behaves correctly when initializing the
 * application container and HTTP layer.
 *
 * This test verifies that the test environment can be set up
 * without errors, and that core service bindings (such as the Http
 * handler) can be registered and resolved properly within the
 * Application instance used during tests.
 *
 * The successful execution of this suite indicates that Omega’s
 * testing foundation is stable and functional for higher-level
 *
 * @category  Tests
 * @package   Testing
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Http::class)]
#[CoversClass(Response::class)]
#[CoversClass(TestCase::class)]
#[CoversClass(TestJsonResponse::class)]
#[CoversClass(TestResponse::class)]
final class TestCaseTest extends TestCase
{
    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws Exception Throw if a generic error occurred.
     */
    protected function setUp(): void
    {
        $this->app = new Application(basePath: dirname(__DIR__));
        $this->app->set(Http::class, fn () => new Http($this->app));

        parent::setUp();
    }

    /**
     * Test json method return test json response.
     *
     * @return void
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     */
    public function testJsonMethodReturnsTestJsonResponse(): void
    {
        $data = ['status' => 'ok', 'data' => ['foo' => 'bar']];

        $response = $this->json(fn() => $data);

        $this->assertInstanceOf(TestJsonResponse::class, $response);
        $this->assertEquals('ok', $response['status']);
        $this->assertEquals('bar', $response['data']['foo']);
    }

    /**
     * Test call method return test response.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws Throwable
     */
    public function testCallMethodReturnsTestResponse(): void
    {
        $this->app->set(Http::class, fn() => new class($this->app) extends Http {
            public function handle($request): Response
            {
                return new Response(['ok' => true]);
            }

            public function terminate($request, $response): void {}
        });

        $response = $this->call('/dummy-url');

        $this->assertInstanceOf(TestResponse::class, $response);
        $this->assertTrue($response['ok']);
    }

    /**
     * Test get method.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws Throwable
     */
    public function testGetMethod(): void
    {
        $this->app->set(Http::class, fn() => new class($this->app) extends Http {
            public function handle($request): Response
            {
                return new Response(['method' => $request->getMethod()]);
            }
            public function terminate($request, $response): void {}
        });

        $response = $this->get('/dummy-get');

        $this->assertInstanceOf(TestResponse::class, $response);
        $this->assertEquals('GET', $response['method']);
    }

    /**
     * Test post method.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws Throwable
     */
    public function testPostMethod(): void
    {
        $this->app->set(Http::class, fn() => new class($this->app) extends Http {
            public function handle($request): Response
            {
                return new Response(['method' => $request->getMethod()]);
            }
            public function terminate($request, $response): void {}
        });

        $response = $this->post('/dummy-post', ['foo' => 'bar']);

        $this->assertInstanceOf(TestResponse::class, $response);
        $this->assertEquals('POST', $response['method']);
    }

    /**
     * Test put merhod.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws Throwable
     */
    public function testPutMethod(): void
    {
        $this->app->set(Http::class, fn() => new class($this->app) extends Http {
            public function handle($request): Response
            {
                return new Response(['method' => $request->getMethod()]);
            }
            public function terminate($request, $response): void {}
        });

        $response = $this->put('/dummy-put', ['foo' => 'bar']);

        $this->assertInstanceOf(TestResponse::class, $response);
        $this->assertEquals('PUT', $response['method']);
    }

    /**
     * Test delete method.
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws Throwable
     */
    public function testDeleteMethod(): void
    {
        $this->app->set(Http::class, fn() => new class($this->app) extends Http {
            public function handle($request): Response
            {
                return new Response(['method' => $request->getMethod()]);
            }
            public function terminate($request, $response): void {}
        });

        $response = $this->delete('/dummy-delete', []);

        $this->assertInstanceOf(TestResponse::class, $response);
        $this->assertEquals('DELETE', $response['method']);
    }

    /**
     * Test json method sets response code and headers.
     *
     * @return void
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function testJsonMethodSetsResponseCodeAndHeaders(): void
    {
        $data = [
            'status'  => 'ok',
            'code'    => 201,
            'headers' => ['X-Test' => 'value'],
        ];

        $response = $this->json(fn() => $data);

        $this->assertInstanceOf(TestJsonResponse::class, $response);

        $this->assertEquals('ok', $response['status']);

        $internalResponse = new ReflectionClass($response)->getProperty('response');
        $internalResponse->setAccessible(true);
        /** @var Response $resp */
        $resp = $internalResponse->getValue($response);

        $this->assertSame(201, $resp->getStatusCode());
        $this->assertArrayHasKey('X-Test', $resp->getHeaders());
        $this->assertSame('value', $resp->getHeaders()['X-Test']);
    }

    /**
     * Test json method handles code and headers.
     *
     * @return void
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function testJsonMethodHandlesCodeAndHeaders(): void
    {
        $dataWithoutExtras = ['status' => 'ok', 'data' => ['foo' => 'bar']];
        $response1 = $this->json(fn() => $dataWithoutExtras);

        $this->assertInstanceOf(TestJsonResponse::class, $response1);
        $this->assertEquals('ok', $response1['status']);
        $this->assertEquals('bar', $response1['data']['foo']);

        $internalResponse1 = new ReflectionClass($response1)->getProperty('response');
        $internalResponse1->setAccessible(true);
        /** @var Response $resp1 */
        $resp1 = $internalResponse1->getValue($response1);

        $this->assertSame(200, $resp1->getStatusCode());
        $this->assertEmpty($resp1->getHeaders());

        $this->json(fn() => ['status' => 'ok', 'code' => 202]);

        $this->json(fn() => ['status' => 'ok', 'headers' => ['X-Test' => 'value']]);

        $dataWithExtras = [
            'status'  => 'ok',
            'code'    => 201,
            'headers' => ['X-Test' => 'value'],
        ];
        $response2 = $this->json(fn() => $dataWithExtras);

        $this->assertInstanceOf(TestJsonResponse::class, $response2);
        $this->assertEquals('ok', $response2['status']);

        $internalResponse2 = new ReflectionClass($response2)->getProperty('response');
        $internalResponse2->setAccessible(true);
        /** @var Response $resp2 */
        $resp2 = $internalResponse2->getValue($response2);

        $this->assertSame(201, $resp2->getStatusCode());
        $this->assertArrayHasKey('X-Test', $resp2->getHeaders());
        $this->assertSame('value', $resp2->getHeaders()['X-Test']);
    }
}
