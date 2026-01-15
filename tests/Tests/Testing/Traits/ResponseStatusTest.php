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

namespace Tests\Testing\Traits;

use Omega\Http\Response;
use Omega\Testing\TestResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * ResponseStatusTest
 *
 * Verifies that the TestResponse assertion helpers correctly evaluate
 * HTTP response status codes. Each test ensures that the corresponding
 * assertion method behaves as expected when wrapping an Omega Response
 * instance with a specific status code.
 *
 * This test suite provides coverage for common HTTP response states,
 * such as success (200), creation (201), no content (204), client errors
 * (400–404), and authorization-related conditions (401–403).
 *
 * These tests ensure that the TestResponse class offers expressive,
 * readable, and reliable test assertions for HTTP responses in Omega
 * applications.
 *
 * @category   Tests
 * @package    Testing
 * @subpackage Traits
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Response::class)]
#[CoversClass(TestResponse::class)]
final class ResponseStatusTest extends TestCase
{
    /**
     * Test it can response assert ok.
     *
     * @return void
     */
    public function testItCanTestResponseAssertOk(): void
    {
        $response = new TestResponse(new Response('test', 200, []));

        $response->assertOk();
    }

    /**
     * Test it can test response assert create.
     *
     * @return void
     */
    public function testItCanTestResponseAssertCreate()
    {
        $response = new TestResponse(new Response('test', 201, []));

        $response->assertCreated();
    }

    /**
     * Test it can test response assert no content.
     *
     * @return void
     */
    public function testItCanTestResponseAssertNoContent(): void
    {
        $response = new TestResponse(new Response('', 204, []));

        $response->assertNoContent();
    }

    /**
     * Test it can response assert bad request.
     *
     * @return void
     */
    public function testItCanTestResponseAssertBadRequest(): void
    {
        $response = new TestResponse(new Response('', 400, []));

        $response->assertBadRequest();
    }

    /**
     * Test it can test response assert unauthorized.
     *
     * @return void
     */
    public function testItCanTestResponseAssertUnauthorized(): void
    {
        $response = new TestResponse(new Response('', 401, []));

        $response->assertUnauthorized();
    }

    /**
     * Test it can tets response assert forbidden.
     *
     * @return void
     */
    public function testItCanTestResponseAssertForbidden(): void
    {
        $response = new TestResponse(new Response('', 403, []));

        $response->assertForbidden();
    }

    /**
     * Test it can test response assert not found.
     *
     * @return void
     */
    public function testItCanTestResponseAssertNotFound(): void
    {
        $response = new TestResponse(new Response('', 404, []));

        $response->assertNotFound();
    }

    /**
     * Test it can test response assert not allowed.
     *
     * @return void
     */
    public function testItCanTestResponseAssertNotAllowed(): void
    {
        $response = new TestResponse(new Response('', 404, []));

        $response->assertNotFound();
    }
}
