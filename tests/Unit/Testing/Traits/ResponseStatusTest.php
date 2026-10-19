<?php

declare(strict_types=1);

namespace Tests\Testing\Traits;

use Omega\Http\Response;
use Omega\Testing\TestResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Response::class)]
#[CoversClass(TestResponse::class)]
final class ResponseStatusTest extends TestCase
{
    public function testAssertsAnOkResponse(): void
    {
        $response = new TestResponse(new Response('test', 200, []));

        $response->assertOk();
    }

    public function testAssertsACreatedResponse(): void
    {
        $response = new TestResponse(new Response('test', 201, []));

        $response->assertCreated();
    }

    public function testAssertsANoContentResponse(): void
    {
        $response = new TestResponse(new Response('', 204, []));

        $response->assertNoContent();
    }

    public function testAssertsABadRequestResponse(): void
    {
        $response = new TestResponse(new Response('', 400, []));

        $response->assertBadRequest();
    }

    public function testAssertsAnUnauthorizedResponse(): void
    {
        $response = new TestResponse(new Response('', 401, []));

        $response->assertUnauthorized();
    }

    public function testAssertsAForbiddenResponse(): void
    {
        $response = new TestResponse(new Response('', 403, []));

        $response->assertForbidden();
    }

    public function testAssertsANotFoundResponse(): void
    {
        $response = new TestResponse(new Response('', 404, []));

        $response->assertNotFound();
    }

    public function testAssertsAMethodNotAllowedResponse(): void
    {
        $response = new TestResponse(new Response('', 405, []));

        $response->assertNotAllowed();
    }
}
