<?php

declare(strict_types=1);

namespace Tests\Testing;

use Omega\Application\Application;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Http;
use Omega\Http\Response;
use Omega\Testing\TestCase;
use Omega\Testing\TestJsonResponse;
use Omega\Testing\TestResponse;
use ReflectionClass;

use function dirname;

covers(Application::class);
covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(EntryNotFoundException::class);
covers(Http::class);
covers(Response::class);
covers(TestCase::class);
covers(TestJsonResponse::class);
covers(TestResponse::class);

beforeEach(function (): void {
    $this->app = new Application(basePath: dirname(__DIR__));
    $this->app->set(Http::class, fn () => new Http($this->app));
});

it('json method returns a test json response', function (): void {
    $data = ['status' => 'ok', 'data' => ['foo' => 'bar']];

    $response = $this->json(fn () => $data);

    expect($response['status'])->toBe('ok');
    $dataPart = $response['data'];
    $this->assertIsArray($dataPart);
    expect($dataPart['foo'])->toBe('bar');
});

it('call method returns a test response', function (): void {
    $this->app->set(Http::class, fn () => new class ($this->app) extends Http {
        public function handle($request): Response
        {
            return new Response(['ok' => true]);
        }

        public function terminate($request, $response): void
        {
        }
    });

    $response = $this->call('/dummy-url');

    expect($response['ok'])->toBeTrue();
});

it('performs a GET request', function (): void {
    $this->app->set(Http::class, fn () => new class ($this->app) extends Http {
        public function handle($request): Response
        {
            return new Response(['method' => $request->getMethod()]);
        }
        public function terminate($request, $response): void
        {
        }
    });

    $response = $this->get('/dummy-get');

    expect($response['method'])->toBe('GET');
});

it('performs a POST request', function (): void {
    $this->app->set(Http::class, fn () => new class ($this->app) extends Http {
        public function handle($request): Response
        {
            return new Response(['method' => $request->getMethod()]);
        }
        public function terminate($request, $response): void
        {
        }
    });

    $response = $this->post('/dummy-post', ['foo' => 'bar']);

    expect($response['method'])->toBe('POST');
});

it('performs a PUT request', function (): void {
    $this->app->set(Http::class, fn () => new class ($this->app) extends Http {
        public function handle($request): Response
        {
            return new Response(['method' => $request->getMethod()]);
        }
        public function terminate($request, $response): void
        {
        }
    });

    $response = $this->put('/dummy-put', ['foo' => 'bar']);

    expect($response['method'])->toBe('PUT');
});

it('performs a DELETE request', function (): void {
    $this->app->set(Http::class, fn () => new class ($this->app) extends Http {
        public function handle($request): Response
        {
            return new Response(['method' => $request->getMethod()]);
        }
        public function terminate($request, $response): void
        {
        }
    });

    $response = $this->delete('/dummy-delete', []);

    expect($response['method'])->toBe('DELETE');
});

it('json method sets the response code and headers', function (): void {
    $data = [
        'status'  => 'ok',
        'code'    => 201,
        'headers' => ['X-Test' => 'value'],
    ];

    $response = $this->json(fn () => $data);

    expect($response['status'])->toBe('ok');

    $internalResponse = new ReflectionClass($response)->getProperty('response');
    $internalResponse->setAccessible(true);
    $resp = $internalResponse->getValue($response);

    if (!$resp instanceof Response) {
        throw new \RuntimeException('Expected a Response instance.');
    }

    expect($resp->getStatusCode())->toBe(201);
    expect($resp->getHeaders())->toHaveKey('X-Test');
    expect($resp->getHeaders()['X-Test'])->toBe('value');
});

it('json method handles responses with and without code and headers', function (): void {
    $dataWithoutExtras = ['status' => 'ok', 'data' => ['foo' => 'bar']];
    $response1 = $this->json(fn () => $dataWithoutExtras);

    expect($response1['status'])->toBe('ok');
    $dataPart = $response1['data'];
    $this->assertIsArray($dataPart);
    expect($dataPart['foo'])->toBe('bar');

    $internalResponse1 = new ReflectionClass($response1)->getProperty('response');
    $internalResponse1->setAccessible(true);
    $resp1 = $internalResponse1->getValue($response1);

    if (!$resp1 instanceof Response) {
        throw new \RuntimeException('Expected a Response instance.');
    }

    expect($resp1->getStatusCode())->toBe(200);
    expect($resp1->getHeaders())->toBeEmpty();

    $this->json(fn () => ['status' => 'ok', 'code' => 202]);

    $this->json(fn () => ['status' => 'ok', 'headers' => ['X-Test' => 'value']]);

    $dataWithExtras = [
        'status'  => 'ok',
        'code'    => 201,
        'headers' => ['X-Test' => 'value'],
    ];
    $response2 = $this->json(fn () => $dataWithExtras);

    expect($response2['status'])->toBe('ok');

    $internalResponse2 = new ReflectionClass($response2)->getProperty('response');
    $internalResponse2->setAccessible(true);
    $resp2 = $internalResponse2->getValue($response2);

    if (!$resp2 instanceof Response) {
        throw new \RuntimeException('Expected a Response instance.');
    }

    expect($resp2->getStatusCode())->toBe(201);
    expect($resp2->getHeaders())->toHaveKey('X-Test');
    expect($resp2->getHeaders()['X-Test'])->toBe('value');
});