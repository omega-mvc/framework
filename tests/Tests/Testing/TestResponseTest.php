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

declare(strict_types=1);

namespace Tests\Testing;

use LogicException;
use Omega\Http\Response;
use Omega\Testing\TestResponse;
use Omega\Testing\Traits\ResponseStatusTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversTrait;
use PHPUnit\Framework\TestCase;

/**
 * TestResponseTest
 *
 * This class contains unit tests for the `TestResponse` class in the Omega framework.
 * It ensures that standard HTTP responses wrapped in `TestResponse` can be asserted
 * correctly. Specifically, it verifies that response content can be retrieved, specific
 * strings can be seen in the response, and HTTP status codes are accurately asserted.
 *
 * These tests confirm that the response testing utilities behave as expected and
 * provide reliable methods for validating HTTP responses in unit tests.
 *
 * @category  Tests
 * @package   Testing
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Response::class)]
#[CoversClass(TestResponse::class)]
#[CoversTrait(ResponseStatusTrait::class)]
final class TestResponseTest extends TestCase
{
    /**
     * Test it can respond assert.
     *
     * @return void
     */
    public function testItCanResponseAssert(): void
    {
        $response = new TestResponse(new Response('test', 200, []));

        $this->assertEquals('test', $response->getContent());
        $response->assertSee('test');
        $response->assertStatusCode(200);
    }

    public function testOffsetExistsAndGet(): void
    {
        $data = ['foo' => 'bar', 'baz' => 123];
        $response = new TestResponse(new Response($data, 200, []));

        // offsetExists
        $this->assertTrue(isset($response['foo']));
        $this->assertFalse(isset($response['nonexistent']));

        // offsetGet
        $this->assertEquals('bar', $response['foo']);
        $this->assertEquals(123, $response['baz']);
        $this->assertNull($response['nonexistent']);
    }

    public function testOffsetSetThrowsLogicException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('TestResponse is read-only.');

        $response = new TestResponse(new Response(['foo' => 'bar'], 200, []));
        $response['foo'] = 'new value';
    }

    public function testOffsetUnsetThrowsLogicException(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('TestResponse is read-only.');

        $response = new TestResponse(new Response(['foo' => 'bar'], 200, []));
        unset($response['foo']);
    }

    public function testGetResponseReturnsOriginalResponse(): void
    {
        $original = new Response(['hello' => 'world'], 200, []);
        $response = new TestResponse($original);

        $this->assertSame($original, $response->getResponse());
    }

    public function testConstructorWithInvalidJsonOrNonArrayContent(): void
    {
        // Passiamo una stringa non JSON: decodifica fallirà → $decoded = []
        $response = new TestResponse(new Response('non-json', 200, []));
        $this->assertIsArray($response['nonexistent'] ?? []);
        $this->assertNull($response['nonexistent']);
    }

    public function testGetContentWithArrayContent(): void
    {
        $data = ['foo' => 'bar'];
        $response = new TestResponse(new Response($data, 200, []));

        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('"foo":"bar"', $content);
    }

    public function testConstructorWithInvalidJsonSetsEmptyDecodedArray(): void
    {
        // Stringa NON JSON valida → json_decode ritorna null
        $response = new TestResponse(new Response('invalid-json', 200, []));

        // Decoded deve essere array vuoto
        $this->assertFalse(isset($response['anything']));
        $this->assertNull($response['anything']);
    }

    public function testConstructorWithValidJsonString(): void
    {
        $json = '{"foo":"bar"}';

        $response = new TestResponse(new Response($json, 200, []));

        $this->assertTrue(isset($response['foo']));
        $this->assertSame('bar', $response['foo']);
    }
}
