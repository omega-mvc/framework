<?php

declare(strict_types=1);

namespace Tests\Testing\Traits;

use Omega\Http\Response;
use Omega\Testing\TestResponse;

covers(Response::class);
covers(TestResponse::class);

it('asserts an ok response', function (): void {
    $response = new TestResponse(new Response('test', 200, []));

    $response->assertOk();
});

it('asserts a created response', function (): void {
    $response = new TestResponse(new Response('test', 201, []));

    $response->assertCreated();
});

it('asserts a no content response', function (): void {
    $response = new TestResponse(new Response('', 204, []));

    $response->assertNoContent();
});

it('asserts a bad request response', function (): void {
    $response = new TestResponse(new Response('', 400, []));

    $response->assertBadRequest();
});

it('asserts an unauthorized response', function (): void {
    $response = new TestResponse(new Response('', 401, []));

    $response->assertUnauthorized();
});

it('asserts a forbidden response', function (): void {
    $response = new TestResponse(new Response('', 403, []));

    $response->assertForbidden();
});

it('asserts a not found response', function (): void {
    $response = new TestResponse(new Response('', 404, []));

    $response->assertNotFound();
});

it('asserts a method not allowed response', function (): void {
    $response = new TestResponse(new Response('', 405, []));

    $response->assertNotAllowed();
});