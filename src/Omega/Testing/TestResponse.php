<?php

/**
 * Part of Omega - Testing Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Testing;

use Omega\Http\Response;
use Omega\Testing\Traits\ResponseStatusTrait;
use PHPUnit\Framework\Assert;

/**
 * Provides convenient assertions for testing HTTP responses.
 *
 * This helper class wraps a Response instance and exposes assertion methods
 * commonly used in integration and feature tests. It allows checking the
 * response status code, inspecting the returned content, and verifying that
 * specific text appears in the output.
 *
 * TestResponse is designed to offer a fluent and expressive API when writing
 * high-level tests that involve controllers, middleware, or the full
 * application lifecycle.
 *
 * @category  Omega
 * @package   Testing
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class TestResponse
{
    use ResponseStatusTrait;

    /** @var Response The underlying HTTP response being tested. */
    protected Response $response;

    /**
     * Create a new TestResponse wrapper instance.
     *
     * @param Response $response The HTTP response to assert on.
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Retrieve the raw content of the wrapped response.
     *
     * @return string The response body content.
     */
    public function getContent(): string
    {
        return $this->response->getContent();
    }

    /**
     * Assert that the given text appears in the response content.
     *
     * @param string $text    The expected substring.
     * @param string $message Optional custom assertion message.
     *
     * @return void
     */
    public function assertSee(string $text, string $message = ''): void
    {
        Assert::assertStringContainsString($text, $this->response->getContent(), $message);
    }

    /**
     * Assert that the response has the expected HTTP status code.
     *
     * @param int    $code    Expected HTTP status code.
     * @param string $message Optional custom assertion message.
     *
     * @return void
     */
    public function assertStatusCode(int $code, string $message = ''): void
    {
        Assert::assertSame($code, $this->response->getStatusCode(), $message);
    }
}
