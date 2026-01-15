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

use Exception;
use Omega\Http\Response;
use Omega\Testing\TestJsonResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * TestJsonResponseTest
 *
 * This class contains unit tests for the `TestJsonResponse` class in the Omega framework.
 * It ensures that JSON responses are correctly handled, accessed as arrays, and properly
 * asserted for common conditions such as equality, truthiness, nullability, emptiness,
 * and standard HTTP response statuses.
 *
 * Each test case wraps a `Response` object inside `TestJsonResponse` and exercises its
 * helper methods to validate expected behavior. The tests verify both data retrieval
 * via array access and assertion helpers for structured JSON responses.
 *
 * These tests confirm that JSON responses in the application behave predictably and
 * allow developers to assert response content effectively in unit tests.
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
#[CoversClass(TestJsonResponse::class)]
final class TestJsonResponseTest extends TestCase
{
    /**
     * Test it can response as array.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanResponseAsArray(): void
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

        $this->assertEquals('ok', $response['status']);
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertEquals('test', $response['test']);
    }

    /**
     * Test it can response assert.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanResponseAssert(): void
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
        $this->assertEquals('ok', $response['status']);
    }

    /**
     * Test it can response assert equal.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanResponseAssertEqual(): void
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

    /**
     * Test it can response assert true.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanResponseAssertTrue(): void
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

    /**
     * Test it can response assert false.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanResponseAssertFalse(): void
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

    /**
     * Test it can response assert null.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanResponseAssertNull(): void
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

    /**
     * Test it can response assert not null.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanResponseAssertNotNull(): void
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

    /**
     * Test it can response assert empty.
     *
     * @return void
     * @throws Exception
     */
    public function testItCanResponseAssertEmpty(): void
    {
        $response = new TestJsonResponse(new Response([
            'status' => 'ok',
            'code'  => 200,
            'data'  => [],
            'error' => null,
        ]));

        $response->assertEmpty('error');
    }

    /**
     * Test it can response assert not empty.
     *
     * @return void
     * @throws Exception
     */
    public function testItCantResponseAssertNotEmpty(): void
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
}
