<?php

declare(strict_types=1);

namespace Tests\Testing;

use Exception;
use Omega\Http\Response;
use Omega\Testing\TestJsonResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Response::class)]
#[CoversClass(TestJsonResponse::class)]
final class TestJsonResponseTest extends TestCase
{
    public function testAllowsArrayAccessToResponseData(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'code'  => 200,
            'data'  => [
                'test' => 'success',
            ],
            'error' => null,
        ]));
        $response['test'] = 'test';

        $this->assertSame('ok', $response['status']);
        $this->assertSame('test', $response['test']);
    }

    public function testReturnsTheDataPartOfTheResponse(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'code'  => 200,
            'data'  => [
                'test' => 'success',
            ],
            'error' => null,
        ]));

        $this->assertEquals(['test' => 'success'], $response->getData());
        $this->assertSame('ok', $response['status']);
    }

    public function testAssertsThatADataKeyEqualsTheExpectedValue(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'code'  => 200,
            'data'  => [
                'test' => 'success',
            ],
            'error' => null,
        ]));

        $response->assertEqual('data.test', 'success');
    }

    public function testAssertsThatADataKeyIsTrue(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'code'  => 200,
            'data'  => [
                'test' => true,
            ],
            'error' => null,
        ]));

        $response->assertTrue('data.test');
    }

    public function testAssertsThatADataKeyIsFalse(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'code'  => 200,
            'data'  => [
                'test' => false,
            ],
            'error' => null,
        ]));

        $response->assertFalse('data.test');
    }

    public function testAssertsThatADataKeyIsNull(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'code'  => 200,
            'data'  => [
                'test' => false,
            ],
            'error' => null,
        ]));

        $response->assertNull('error');
    }

    public function testAssertsThatADataKeyIsNotNull(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'code'  => 200,
            'data'  => [
                'test' => false,
            ],
            'error' => [
                'test' => 'some error',
            ],
        ]));

        $response->assertNotNull('error');
    }

    public function testAssertsThatTheDataPayloadIsEmpty(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'code'  => 200,
            'data'  => [],
            'error' => null,
        ]));

        $response->assertEmpty('error');
    }

    public function testAssertsThatTheDataPayloadIsNotEmpty(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'code'  => 200,
            'data'  => [
                'test' => false,
            ],
            'error' => null,
        ]));

        $response->assertNotEmpty('error');
    }

    public function testThrowsWhenTheResponseContentIsNotAnArray(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Response body is not Array.');

        new TestJsonResponse(new Response('not-an-array'));
    }

    public function testReplacesTheResponseData(): void
    {
        $response = new TestJsonResponse(new Response([
            'data' => [],
        ]));

        $response->setResponseData([
            'data' => ['changed' => true],
        ]);

        $this->assertEquals(['changed' => true], $response->getData());
    }

    public function testChecksWhetherAnOffsetExists(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'data' => [],
        ]));

        $this->assertTrue(isset($response['status']));
        $this->assertFalse(isset($response['missing']));
    }

    public function testUnsetsAnOffset(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'data' => [],
        ]));

        unset($response['status']);

        $this->assertFalse(isset($response['status']));
    }

    public function testReturnsFalseWhenTheOffsetIsNotAStringOrInt(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'data' => [],
        ]));

        $this->assertFalse($response->offsetExists(1.5));
    }

    public function testReturnsNullWhenTheOffsetIsNotAStringOrInt(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'data' => [],
        ]));

        $this->assertNull($response->offsetGet(1.5));
    }

    public function testDoesNotSetTheValueWhenTheOffsetIsNotAStringOrInt(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'data' => [],
        ]));

        $response->offsetSet(1.5, 'ignored');

        $this->assertTrue($response->offsetExists('status'));
        $this->assertFalse($response->offsetExists('ignored'));
    }

    public function testDoesNotUnsetTheValueWhenTheOffsetIsNotAStringOrInt(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'data' => [],
        ]));

        $response->offsetUnset(1.5);

        $this->assertTrue($response->offsetExists('status'));
    }

    public function testSupportsIntegerOffsets(): void
    {
        $response = new TestJsonResponse(new Response([
            0     => 'zero',
            'var' => 'ok',
        ]));

        $this->assertTrue($response->offsetExists(0));

        $this->assertSame('zero', $response->offsetGet(0));

        $response->offsetSet(0, 'changed');

        $this->assertSame('changed', $response->offsetGet(0));

        $response->offsetUnset(0);

        $this->assertFalse($response->offsetExists(0));
    }
}
