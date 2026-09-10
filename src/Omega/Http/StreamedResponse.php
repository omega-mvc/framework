<?php

/**
 * Part of Omega - Http Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Http;

use Omega\Http\Exceptions\StreamedResponseCallableException;

/**
 * Class StreamedResponse
 *
 * Represents an HTTP response that streams its content directly to the client.
 * It allows sending output progressively via a user-provided callable.
 * This is useful for streaming large data, files, or real-time output.
 *
 * @category  Omega
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class StreamedResponse extends Response
{
    /** @var (callable(): void)|null The callback used to generate the response content when streaming. */
    private $callableStream;

    /** @var bool Indicates whether the response has already been streamed. */
    private bool $isStream;

    /**
     * Create a new StreamedResponse.
     *
     * @param (callable(): void)|null $callableStream A callable that generates the response content.
     * @param int                     $responseCode   HTTP response code to send.
     * @param array<string, string>   $headers        Headers to include in the response.
     * @return void
     */
    public function __construct(
        $callableStream,
        int $responseCode = Response::HTTP_OK,
        array $headers = [],
    ) {
        $this->setStream($callableStream);
        $this->setResponseCode($responseCode);
        $this->headers   = new HeaderCollection($headers);
        $this->isStream = false;
    }

    /**
     * Set the stream callback.
     *
     * @param (callable(): void)|null $callableStream A callable to generate the response content.
     * @return StreamedResponse Returns the current instance for method chaining.
     */
    public function setStream(?callable $callableStream): self
    {
        $this->callableStream = $callableStream;

        return $this;
    }

    /**
     * Send the response content by invoking the stream callback.
     *
     * @throws StreamedResponseCallableException Thrown if no callable was provided for streaming.
     */
    protected function sendContent(): void
    {
        if ($this->isStream) {
            return;
        }

        $this->isStream = true;

        if (null === $this->callableStream) {
            throw new StreamedResponseCallableException();
        }

        ($this->callableStream)();
    }
}
