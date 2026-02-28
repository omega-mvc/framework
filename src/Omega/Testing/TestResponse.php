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
class TestResponse implements \ArrayAccess
{
    use ResponseStatusTrait;

    /** @var Response The underlying HTTP response being tested. */
    protected Response $response;

    /** @var array Decoded response content (associativo). */
    protected array $decoded = [];

    /**
     * Create a new TestResponse wrapper instance.
     *
     * @param Response $response The HTTP response to assert on.
     */
    public function __construct(Response $response)
    {
        $this->response = $response;

        $content = $response->getContent();

        // Se è array lo prendiamo così, altrimenti proviamo a decodificare JSON
        if (is_array($content)) {
            $this->decoded = $content;
        } else {
            $decoded = json_decode((string)$content, true);
            $this->decoded = is_array($decoded) ? $decoded : [];
        }
    }

    /**
     * Retrieve the raw content of the wrapped response.
     *
     * @return string The response body content.
     */
    public function getContent(): string
    {
        $content = $this->response->getContent();
        return is_string($content) ? $content : json_encode($content);
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
        Assert::assertStringContainsString($text, $this->getContent(), $message);
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

    /** ------------------- ArrayAccess ------------------- */

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->decoded[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->decoded[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('TestResponse is read-only.');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('TestResponse is read-only.');
    }

    /**
     * Retrieve the underlying Response instance.
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
