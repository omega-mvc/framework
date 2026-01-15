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

/** @noinspection PhpUndefinedFunctionInspection */
/** @noinspection PhpRegExpRedundantModifierInspection */

declare(strict_types=1);

namespace Omega\Http;

use Exception;

use function array_merge;
use function count;
use function fastcgi_finish_request;
use function flush;
use function function_exists;
use function header;
use function header_remove;
use function in_array;
use function is_array;
use function is_numeric;
use function is_string;
use function json_encode;
use function ob_end_clean;
use function ob_end_flush;
use function ob_get_status;
use function preg_replace;
use function sprintf;
use function str_contains;

use const PHP_OUTPUT_HANDLER_CLEANABLE;
use const PHP_OUTPUT_HANDLER_FLUSHABLE;
use const PHP_OUTPUT_HANDLER_REMOVABLE;
use const PHP_SAPI;

/**
 * Class Response
 *
 * Represents an HTTP response including status code, headers, content, and protocol version.
 * Provides methods to send the response to the client in various formats (HTML, JSON, plain text),
 * manage headers, and handle output buffering.
 *
 * @category  Omega
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Response
{
    // HTTP Status Codes
    public const int HTTP_OK = 200;
    public const int HTTP_CREATED = 201;
    public const int HTTP_ACCEPTED = 202;
    public const int HTTP_NON_AUTHORITATIVE_INFORMATION = 203;
    public const int HTTP_NO_CONTENT = 204;
    public const int HTTP_MOVED_PERMANENTLY = 301;
    public const int HTTP_BAD_REQUEST = 400;
    public const int HTTP_UNAUTHORIZED = 401;
    public const int HTTP_PAYMENT_REQUIRED = 402;
    public const int HTTP_FORBIDDEN = 403;
    public const int HTTP_NOT_FOUND = 404;
    public const int HTTP_METHOD_NOT_ALLOWED = 405;

    /**
     * Standard HTTP status texts associated with codes.
     *
     * @var array<int, string>
     */
    public static array $statusTexts = [
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        301 => 'Moved Permanently',
        304 => 'Not Modified',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
    ];

    /** @var string|array Http body content. */
    private string|array $content;

    /** @var int Http response code. */
    private int $responseCode;

    /** @var HeaderCollection Collection of headers to send to client. */
    public HeaderCollection $headers;

    /** @var array<int, string> List of headers to remove before sending. */
    private array $removeHeaders = [];

    /** @var bool Whether to remove default headers before sending. */
    private bool $removeDefaultHeaders = false;

    /** @var string Content type of the response. */
    private string $contentType = 'text/html';

    /** @var string HTTP protocol version (1.0 or 1.1). */
    private string $protocolVersion;

    /** @var int JSON encoding options (default: JSON_NUMERIC_CHECK). */
    protected int $encodingOption = JSON_NUMERIC_CHECK;

    /**
     * Response constructor.
     *
     * @param string|array         $content      Content to send to the client.
     * @param int                  $responseCode HTTP response code.
     * @param array<string, string> $headers     Headers to send with the response.
     */
    public function __construct(array|string $content = '', int $responseCode = Response::HTTP_OK, array $headers = [])
    {
        $this->setContent($content);
        $this->setResponseCode($responseCode);
        $this->headers = new HeaderCollection($headers);
        $this->setProtocolVersion('1.1');
    }

    /**
     * Return raw HTTP response string including status line, headers, and content.
     *
     * @return string Full HTTP response as string.
     */
    public function __toString(): string
    {
        $responseCode   = $this->responseCode;
        $responseText   = Response::$statusTexts[$responseCode] ?? 'ok';
        $responseHeader = sprintf('HTTP/%s %s %s', $this->getProtocolVersion(), $responseCode, $responseText);

        $headerLines = (string) $this->headers;
        $content     = is_array($this->content)
            ? json_encode($this->content, $this->encodingOption)
            : $this->content;

        return
            $responseHeader . "\r\n" .
            $headerLines . "\r\n" .
            "\r\n" .
            $content;
    }

    /**
     * Send HTTP headers to the client, including response code.
     *
     * @return void
     */
    private function sendHeaders(): void
    {
        if (headers_sent()) {
            return;
        }

        // remove default header
        if ($this->removeDefaultHeaders) {
            header_remove();
        }

        // header response code
        $responseCode     = $this->responseCode;
        $responseText     = Response::$statusTexts[$responseCode] ?? 'unknown status';
        $responseTemplate = sprintf('HTTP/1.1 %s %s', $responseCode, $responseText);
        header($responseTemplate);

        // header
        $this->headers->set('Content-Type', $this->contentType);
        // add custom header
        foreach ($this->headers as $key => $header) {
            header($key . ':' . $header);
        }

        // remove header
        foreach ($this->removeHeaders as $header) {
            header_remove($header);
        }
    }

    /**
     * Send the response content to the client.
     *
     * @return void
     */
    protected function sendContent(): void
    {
        echo is_array($this->content)
            ? json_encode($this->content, $this->encodingOption)
            : $this->content;
    }

    /**
     * Cleans or flushes output buffers up to target level.
     *
     * @param int  $targetLevel Target buffer level.
     * @param bool $flush       Whether to flush (true) or clean (false) buffers.
     * @return void
     */
    public static function closeOutputBuffers(int $targetLevel, bool $flush): void
    {
        $status = ob_get_status(true);
        $level  = count($status);
        $flags  = PHP_OUTPUT_HANDLER_REMOVABLE | ($flush ? PHP_OUTPUT_HANDLER_FLUSHABLE : PHP_OUTPUT_HANDLER_CLEANABLE);

        while (
            $level-- > $targetLevel
            && ($s = $status[$level])
            && (!isset($s['del']) ? !isset($s['flags']) || ($s['flags'] & $flags) === $flags : $s['del'])
        ) {
            if ($flush) {
                ob_end_flush();
            } else {
                ob_end_clean();
            }
        }
    }

    /**
     * Send the response (headers and content) to the client.
     *
     * @return self
     * @noinspection SpellCheckingInspection
     */
    public function send(): self
    {
        $this->sendHeaders();
        $this->sendContent();

        /** @noinspection SpellCheckingInspection */
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();

            return $this;
        }

        if (function_exists('litespeed_finish_request')) {
            litespeed_finish_request();

            return $this;
        }

        if (!in_array(PHP_SAPI, ['cli', 'phpdbg'], true)) {
            static::closeOutputBuffers(0, true);
            flush();

            return $this;
        }

        return $this;
    }

    /**
     * Set response content type to JSON and optionally override content.
     *
     * @param string|array|null $content Optional content to send as JSON.
     * @return self
     */
    public function json(string|array|null $content = null): self
    {
        $this->contentType = 'application/json';

        if ($content != null) {
            $this->setContent($content);
        }

        return $this;
    }

    /**
     * Set response content type to HTML and optionally minify.
     *
     * @param bool $minify Whether to minify HTML content.
     * @return self
     */
    public function html(bool $minify = false): self
    {
        $this->contentType = 'text/html';

        if (!is_array($this->content) && $minify) {
            /** @var string $stringContent */
            $stringContent = $this->content;
            $stringContent =  $this->minify($stringContent);

            $this->setContent($stringContent);
        }

        return $this;
    }

    /**
     * Set response content type to plain text.
     *
     * @return self
     */
    public function plainText(): self
    {
        $this->contentType = 'text/html';

        return $this;
    }

    /**
     * Minify HTML content by removing extra whitespaces and comments.
     *
     * @param string $content Raw HTML content.
     * @return string Minified HTML content.
     */
    private function minify(string $content): string
    {
        $search = [
            '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // strip whitespaces before tags, except space
            '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/', // Remove HTML comments
        ];

        $replace = [
            '>',
            '<',
            '\\1',
            '',
        ];

        return preg_replace($search, $replace, $content) ?? $content;
    }

    /**
     * Exit the application immediately.
     *
     * @return void
     * @noinspection PhpNoReturnAttributeCanBeAddedInspection
     */
    public function close(): void
    {
        exit;
    }

    /**
     * Set response content.
     *
     * @param string|array $content Content to send.
     * @return self
     */
    public function setContent(string|array $content): self
    {
        $this->content  = $content;

        return $this;
    }

    /**
     * Set the HTTP response code for this response.
     *
     * @param int $responseCode The HTTP status code to set (e.g., 200, 404).
     * @return self Returns the current instance for method chaining.
     */
    public function setResponseCode(int $responseCode): self
    {
        $this->responseCode = $responseCode;

        return $this;
    }

    /**
     * Replace the current set of headers with a new collection.
     *
     * @param array<string, string> $headers Associative array of headers to set (name => value).
     * @return self Returns the current instance for method chaining.
     * @throws Exception Throws if an invalid header is encountered.
     * @todo Deprecated: use the headers property directly instead of this method.
     */
    public function setHeaders(array $headers): self
    {
        $this->headers->clear();

        foreach ($headers as $headerName => $header) {
            if (is_numeric($headerName)) {
                if (!str_contains($header, ':')) {
                    continue;
                }

                $this->headers->setRaw($header);
                continue;
            }

            $this->headers->set($headerName, $header);
        }

        return $this;
    }

    /**
     * Set the HTTP protocol version for this response.
     *
     * @param string $version The HTTP version to use (e.g., '1.0', '1.1').
     * @return self Returns the current instance for method chaining.
     */
    public function setProtocolVersion(string $version): self
    {
        $this->protocolVersion = $version;

        return $this;
    }

    /**
     * Set the Content-Type header of the response.
     *
     * @param string $contentType The MIME type of the response content (e.g., 'text/html', 'application/json').
     * @return self Returns the current instance for method chaining.
     */
    public function setContentType(string $contentType): self
    {
        $this->contentType = $contentType;

        return $this;
    }

    /**
     * Remove one or more headers from the response before sending.
     *
     * @param string|array<int, string> $headers Header name or list of header names to remove.
     * @return self Returns the current instance for method chaining.
     */
    public function removeHeader(string|array $headers): self
    {
        if (is_string($headers)) {
            $this->removeHeaders[] = $headers;

            return $this;
        }

        // @todo deprecated use `removeHeaders` instead
        $this->removeHeaders($headers);

        return $this;
    }

    /**
     * Remove multiple headers from the response before sending.
     *
     * @param array<int, string> $headers List of header names to remove.
     * @return self Returns the current instance for method chaining.
     */
    public function removeHeaders(array $headers): self
    {
        $this->removeHeaders = [];
        foreach ($headers as $header) {
            $this->removeHeaders[] = $header;
        }

        return $this;
    }

    /**
     * Enable or disable removal of default headers before sending.
     *
     * @param bool $removeDefaultHeader Set to true to remove default headers.
     * @return self Returns the current instance for method chaining.
     */
    public function removeDefaultHeader(bool $removeDefaultHeader = false): self
    {
        $this->removeDefaultHeaders = $removeDefaultHeader;

        return $this;
    }

    /**
     * Add or update a header in the response.
     *
     * @param string      $header The header name or full raw header string if $value is null.
     * @param string|null $value  The header value. If null, $header is treated as a raw header line.
     * @return self Returns the current instance for method chaining.
     * @throws Exception Throws if an invalid header is encountered.
     * @todo Deprecated: use the headers property directly instead of this method.
     */
    public function header(string $header, ?string $value = null): self
    {
        if (null === $value) {
            $this->headers->setRaw($header);

            return $this;
        }

        $this->headers->set($header, $value);

        return $this;
    }

    /**
     * Retrieve all headers as an associative array.
     *
     * @return array<string, string> Array of headers (name => value).
     * @todo Deprecated: use the headers property directly instead of this method.
     */
    public function getHeaders(): array
    {
        return $this->headers->toArray();
    }

    /**
     * Get the current HTTP response code.
     *
     * @return int The current HTTP status code.
     */
    public function getStatusCode(): int
    {
        return $this->responseCode;
    }

    /**
     * Get the current response content.
     *
     * @return string|array The content of the response.
     */
    public function getContent(): string|array
    {
        return $this->content;
    }

    /**
     * Get the HTTP protocol version.
     *
     * @return string The HTTP version (e.g., '1.0', '1.1').
     */
    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    /**
     * Get the Content-Type of the response.
     *
     * @return string The MIME type of the response content (e.g., 'text/html').
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Follow headers from a request and apply them to this response.
     *
     * @param Request            $request    The HTTP request to follow headers from.
     * @param array<int, string> $headerName Optional list of headers to copy from the request.
     * @return self Returns the current instance for method chaining.
     */
    public function followRequest(Request $request, array $headerName = []): self
    {
        $followRule = array_merge($headerName, [
            'cache-control',
            'content-type',
        ]);

        foreach ($followRule as $rule) {
            if ($request->hasHeader($rule)) {
                $this->headers->set($rule, $request->getHeaders($rule));
            }
        }

        return $this;
    }

    /**
     * Check if the response is informational (1xx).
     *
     * @return bool True if response code is 1xx, false otherwise.
     */
    public function isInformational(): bool
    {
        return $this->responseCode > 99 && $this->responseCode < 201;
    }

    /**
     * Check if the response is successful (2xx).
     *
     * @return bool True if response code is 2xx, false otherwise.
     */
    public function isSuccessful(): bool
    {
        return $this->responseCode > 199 && $this->responseCode < 301;
    }

    /**
     * Check if the response is a redirection (3xx).
     *
     * @return bool True if response code is 3xx, false otherwise.
     */
    public function isRedirection(): bool
    {
        return $this->responseCode > 299 && $this->responseCode < 401;
    }

    /**
     * Check if the response is a client error (4xx).
     *
     * @return bool True if response code is 4xx, false otherwise.
     */
    public function isClientError(): bool
    {
        return $this->responseCode > 399 && $this->responseCode < 501;
    }

    /**
     * Check if the response is a server error (5xx).
     *
     * @return bool True if response code is 5xx, false otherwise.
     */
    public function isServerError(): bool
    {
        return $this->responseCode > 499 && $this->responseCode < 601;
    }
}
