<?php

/**
 * Part of Omega - Tests\Http Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Http;

use Omega\Http\RedirectResponse;
use Omega\Testing\TestResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class RedirectResponseTest
 *
 * This test suite verifies the behavior of the RedirectResponse class,
 * ensuring that HTTP redirections are properly constructed and exposed.
 *
 * It validates:
 * - The default redirect status code (302).
 * - The presence and correctness of the "Location" header.
 * - The generated response content indicating the redirect target.
 * - Integration with the TestResponse helper for fluent assertions.
 *
 * The purpose of this test is to guarantee that redirect responses
 * comply with expected HTTP semantics and provide consistent output.
 *
 * @category  Tests
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(RedirectResponse::class)]
#[CoversClass(TestResponse::class)]
final class RedirectResponseTest extends TestCase
{
    /**
     * Test it can get response content.
     *
     * @return void
     */
    public function testItCanGetResponseContent(): void
    {
        $res      = new RedirectResponse('/login');
        $redirect = new TestResponse($res);

        $redirect->assertSee('Redirecting to /login');
        $redirect->assertStatusCode(302);

        foreach ($res->getHeaders() as $key => $value) {
            if ('Location' === $key) {
                $this->assertEquals('/login', $value);
            }
        }
    }
}
