<?php

declare(strict_types=1);

namespace Tests\Testing;

use LogicException;
use Omega\Http\Response;
use Omega\Testing\TestResponse;
use Omega\Testing\Traits\ResponseStatusTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Response::class)]
#[CoversClass(TestResponse::class)]
#[CoversClass(ResponseStatusTrait::class)]
final class TestResponseTest extends TestCase
{
    public function testCanRespondAssert(): void
    {
        $response = new TestResponse(new Response('test', 200, []));

        $this->assertSame('test', $response->getContent());
        $response->assertSee('test');
        $response->assertStatusCode(200);
    }

    public function testChecksOffsetExistsAndGet(): void
    {
        $response = new TestResponse(new Response(['foo' => 'bar', 'baz' => 123], 200, []));

        $this->assertTrue(isset($response['foo']));
        $this->assertFalse(isset($response['nonexistent']));

        $this->assertSame('bar', $response['foo']);
        $this->assertSame(123, $response['baz']);
        $this->assertNull($response['nonexistent']);
    }

    public function testThrowsLogicExceptionOnOffsetSet(): void
    {
        $response = new TestResponse(new Response(['foo' => 'bar'], 200, []));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('TestResponse is read-only.');

        $response['foo'] = 'new value';
    }

    public function testThrowsLogicExceptionOnOffsetUnset(): void
    {
        $response = new TestResponse(new Response(['foo' => 'bar'], 200, []));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('TestResponse is read-only.');

        unset($response['foo']);
    }

    public function testReturnsTheOriginalResponse(): void
    {
        $original = new Response(['hello' => 'world'], 200, []);
        $response = new TestResponse($original);

        $this->assertSame($original, $response->getResponse());
    }

    public function testHandlesInvalidJsonOrNonArrayContent(): void
    {
        $response = new TestResponse(new Response('non-json', 200, []));

        $this->assertIsArray($response['nonexistent'] ?? []);
        $this->assertNull($response['nonexistent']);
    }

    public function testGetsContentWithArrayContent(): void
    {
        $response = new TestResponse(new Response(['foo' => 'bar'], 200, []));

        $this->assertStringContainsString('"foo":"bar"', $response->getContent());
    }

    public function testSetsEmptyDecodedArrayForInvalidJson(): void
    {
        $response = new TestResponse(new Response('invalid-json', 200, []));

        $this->assertFalse(isset($response['anything']));
        $this->assertNull($response['anything']);
    }

    public function testDecodesAValidJsonString(): void
    {
        $response = new TestResponse(new Response('{"foo":"bar"}', 200, []));

        $this->assertTrue(isset($response['foo']));
        $this->assertSame('bar', $response['foo']);
    }
}
