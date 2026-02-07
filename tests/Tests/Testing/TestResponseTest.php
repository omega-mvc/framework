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

use Omega\Http\Response;
use Omega\Testing\TestResponse;
use PHPUnit\Framework\Attributes\CoversClass;
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
}
