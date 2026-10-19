<?php

declare(strict_types=1);

namespace Tests\Testing;

use Exception;
use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Http;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\Testing\TestCase as OmegaTestCase;
use Omega\Testing\TestJsonResponse;
use Omega\Testing\TestResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use Tests\TestCase;

use function dirname;

#[CoversClass(Application::class)]
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
#[CoversClass(Http::class)]
#[CoversClass(Response::class)]
#[CoversClass(OmegaTestCase::class)]
#[CoversClass(TestJsonResponse::class)]
#[CoversClass(TestResponse::class)]
final class TestCaseTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app = new Application(basePath: dirname(__DIR__));
        $this->app->set(Http::class, fn () => new Http($this->app));
    }

    public function testJsonMethodReturnsATestJsonResponse(): void
    {
        $data = ['status' => 'ok', 'data' => ['foo' => 'bar']];

        $response = $this->json(fn () => $data);

        $this->assertSame('ok', $response['status']);
        $dataPart = $response['data'];
        $this->assertIsArray($dataPart);
        $this->assertSame('bar', $dataPart['foo']);
    }

    public function testCallMethodReturnsATestResponse(): void
    {
        $this->app->set(Http::class, fn () => new class ($this->app) extends Http {
            public function handle(Request $request): Response
            {
                return new Response(['ok' => true]);
            }

            public function terminate(Request $request, Response $response): void
            {
            }
        });

        $response = $this->call('/dummy-url');

        $this->assertTrue($response['ok']);
    }

    public function testPerformsAGetRequest(): void
    {
        $this->app->set(Http::class, fn () => new class ($this->app) extends Http {
            public function handle(Request $request): Response
            {
                return new Response(['method' => $request->getMethod()]);
            }

            public function terminate(Request $request, Response $response): void
            {
            }
        });

        $response = $this->get('/dummy-get');

        $this->assertSame('GET', $response['method']);
    }

    public function testPerformsAPostRequest(): void
    {
        $this->app->set(Http::class, fn () => new class ($this->app) extends Http {
            public function handle(Request $request): Response
            {
                return new Response(['method' => $request->getMethod()]);
            }

            public function terminate(Request $request, Response $response): void
            {
            }
        });

        $response = $this->post('/dummy-post', ['foo' => 'bar']);

        $this->assertSame('POST', $response['method']);
    }

    public function testPerformsAPutRequest(): void
    {
        $this->app->set(Http::class, fn () => new class ($this->app) extends Http {
            public function handle(Request $request): Response
            {
                return new Response(['method' => $request->getMethod()]);
            }

            public function terminate(Request $request, Response $response): void
            {
            }
        });

        $response = $this->put('/dummy-put', ['foo' => 'bar']);

        $this->assertSame('PUT', $response['method']);
    }

    public function testPerformsADeleteRequest(): void
    {
        $this->app->set(Http::class, fn () => new class ($this->app) extends Http {
            public function handle(Request $request): Response
            {
                return new Response(['method' => $request->getMethod()]);
            }

            public function terminate(Request $request, Response $response): void
            {
            }
        });

        $response = $this->delete('/dummy-delete', []);

        $this->assertSame('DELETE', $response['method']);
    }

    public function testJsonMethodSetsTheResponseCodeAndHeaders(): void
    {
        $data = [
            'status'  => 'ok',
            'code'    => 201,
            'headers' => ['X-Test' => 'value'],
        ];

        $response = $this->json(fn () => $data);

        $this->assertSame('ok', $response['status']);

        $internalResponse = new ReflectionClass($response)->getProperty('response');
        $resp = $internalResponse->getValue($response);

        if (!$resp instanceof Response) {
            throw new Exception('Expected a Response instance.');
        }

        $this->assertSame(201, $resp->getStatusCode());
        $this->assertArrayHasKey('X-Test', $resp->getHeaders());
        $this->assertSame('value', $resp->getHeaders()['X-Test']);
    }

    public function testJsonMethodHandlesResponsesWithAndWithoutCodeAndHeaders(): void
    {
        $dataWithoutExtras = ['status' => 'ok', 'data' => ['foo' => 'bar']];
        $response1 = $this->json(fn () => $dataWithoutExtras);

        $this->assertSame('ok', $response1['status']);
        $dataPart = $response1['data'];
        $this->assertIsArray($dataPart);
        $this->assertSame('bar', $dataPart['foo']);

        $internalResponse1 = new ReflectionClass($response1)->getProperty('response');
        $resp1 = $internalResponse1->getValue($response1);

        if (!$resp1 instanceof Response) {
            throw new Exception('Expected a Response instance.');
        }

        $this->assertSame(200, $resp1->getStatusCode());
        $this->assertEmpty($resp1->getHeaders());

        $this->json(fn () => ['status' => 'ok', 'code' => 202]);

        $this->json(fn () => ['status' => 'ok', 'headers' => ['X-Test' => 'value']]);

        $dataWithExtras = [
            'status'  => 'ok',
            'code'    => 201,
            'headers' => ['X-Test' => 'value'],
        ];
        $response2 = $this->json(fn () => $dataWithExtras);

        $this->assertSame('ok', $response2['status']);

        $internalResponse2 = new ReflectionClass($response2)->getProperty('response');
        $resp2 = $internalResponse2->getValue($response2);

        if (!$resp2 instanceof Response) {
            throw new Exception('Expected a Response instance.');
        }

        $this->assertSame(201, $resp2->getStatusCode());
        $this->assertArrayHasKey('X-Test', $resp2->getHeaders());
        $this->assertSame('value', $resp2->getHeaders()['X-Test']);
    }

    public function testJsonMethodThrowsAnExceptionWhenTheResponseBodyIsNotAnArray(): void
    {
        $this->expectException(Exception::class);

        $this->json(fn () => 'not-an-array');
    }

    public function testJsonMethodIgnoresNonNumericCodeAndNonArrayHeaders(): void
    {
        $data = [
            'status'  => 'ok',
            'code'    => 'not-a-number',
            'headers' => 'not-an-array',
        ];

        $response = $this->json(fn () => $data);

        $internalResponse = new ReflectionClass($response)->getProperty('response');
        $resp = $internalResponse->getValue($response);

        if (!$resp instanceof Response) {
            throw new Exception('Expected a Response instance.');
        }

        $this->assertSame(200, $resp->getStatusCode());
        $this->assertEmpty($resp->getHeaders());
    }

    public function testJsonMethodFiltersNonStringHeaders(): void
    {
        $data = [
            'status'  => 'ok',
            'headers' => [
                'X-Valid' => 'keep',
                'X-Int'   => 123,
                42        => 'numeric-key',
            ],
        ];

        $response = $this->json(fn () => $data);

        $internalResponse = new ReflectionClass($response)->getProperty('response');
        $resp = $internalResponse->getValue($response);

        if (!$resp instanceof Response) {
            throw new Exception('Expected a Response instance.');
        }

        $this->assertSame(['X-Valid' => 'keep'], $resp->getHeaders());
    }
}
