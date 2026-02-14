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

use Omega\Http\Exceptions\StreamedResponseCallableException;
use Omega\Http\Request;
use Omega\Http\StreamedResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class StreamedResponseTest
 *
 * This test suite verifies the behavior of the StreamedResponse class,
 * ensuring that streamed callbacks are correctly executed and managed.
 *
 * It covers:
 * - Proper initialization through the constructor with status and headers.
 * - Header propagation from a Request instance via followRequest().
 * - Single execution of the streaming callback when sending content.
 * - Exception handling when the provided stream callback is not callable.
 *
 * The goal is to ensure that streamed responses behave predictably,
 * respect HTTP semantics, and safely handle invalid stream definitions.
 *
 * @category  Tests
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(StreamedResponseCallableException::class)]
#[CoversClass(Request::class)]
#[CoversClass(StreamedResponse::class)]
final class StreamedResponseTest extends TestCase
{
    /**
     * Test it can use constructor.
     *
     * @return void
     */
    public function testItCanUseConstructor(): void
    {
        $response = new StreamedResponse(function () { echo 'php'; }, 200, ['Content-Type' => 'text/plain']);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaders()['Content-Type']);
    }

    /**
     * Test it can create stream response using request.
     *
     * @return void
     */
    public function testItCanCreateStreamResponseUsingRequest(): void
    {
        $response = new StreamedResponse(function () { echo 'php'; }, 200, ['Content-Type' => 'application/json']);
        $request  = new Request('', [], [], [], [], [], ['Content-Type' => 'text/plain'], 'HEAD');
        $response->followRequest($request, ['Content-Type']);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('text/plain', $response->getHeaders()['Content-Type']);
    }

    /**
     * Test it can send content.
     *
     * @return void
     */
    public function testItCanSendContent(): void
    {
        $called = 0;

        $response = new StreamedResponse(function () use (&$called) { $called++; });

        (fn () => $this->{'sendContent'}())->call($response);
        $this->assertEquals(1, $called);

        (fn () => $this->{'sendContent'}())->call($response);
        $this->assertEquals(1, $called);
    }

    /**
     * Test it  can send content with non-callable.
     *
     * @return void
     */
    public function testItCanSendContentWithNonCallable(): void
    {
        $this->expectException(StreamedResponseCallableException::class);
        $response = new StreamedResponse(null);
        (fn () => $this->{'sendContent'}())->call($response);
    }
}
