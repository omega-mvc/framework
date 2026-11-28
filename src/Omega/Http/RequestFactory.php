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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;

use function apache_request_headers;
use function array_change_key_case;
use function array_filter;
use function array_keys;
use function array_map;
use function array_reduce;
use function array_values;
use function base64_encode;
use function file_get_contents;
use function function_exists;
use function implode;
use function is_array;
use function is_int;
use function is_scalar;
use function is_string;
use function preg_match;
use function strncmp;
use function strtr;
use function strval;
use function substr;
use function trim;

use const ARRAY_FILTER_USE_KEY;

/**
 * Class RequestFactory
 *
 * Factory class for creating Request instances.
 * Provides helper methods to capture the current HTTP request from PHP globals
 * and populate a Request object with query parameters, post data, headers,
 * cookies, files, HTTP method, client IP, and raw request body.
 *
 * @category  Omega
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class RequestFactory
{
    /**
     * Capture the current HTTP request and return a Request instance.
     *
     * @return Request Returns a Request object populated from global PHP variables.
     */
    public static function capture(): Request
    {
        return new self()->getFromGlobal();
    }

    /**
     * Build an Omega Request from a PSR-7 ServerRequestInterface.
     *
     * This is the canonical entry point for persistent workers (e.g.
     * RoadRunner), where the framework must not rely on PHP superglobals
     * such as `$_SERVER`, `$_GET`, `$_POST`, `$_COOKIE` or `php://input`.
     * Instead, all request data is taken directly from the PSR-7 object.
     *
     * @param ServerRequestInterface $psr7 The PSR-7 server request.
     * @return Request The equivalent Omega Request.
     */
    public static function fromPsr7ServerRequest(ServerRequestInterface $psr7): Request
    {
        $factory     = new self();
        $psr7Headers = $psr7->getHeaders();

        /** @var array<string, string> $headers */
        $headers = array_change_key_case(array_map([$factory, 'implodeHeaderValues'], $psr7Headers));

        $uri     = $psr7->getUri();
        $method  = $psr7->getMethod() ?: 'GET';
        $remote  = $psr7->getServerParams()['REMOTE_ADDR'] ?? '::1';

        if ($method === 'POST') {
            $override = $psr7->getHeaderLine('X-HTTP-Method-Override');
            if (preg_match('#^[A-Z]+$#D', $override)) {
                $method = $override;
            }
        }

        return new Request(
            (string) $uri,
            $factory->normalizeToStringArray($psr7->getQueryParams()),
            $factory->normalizeToStringArray(is_array($psr7->getParsedBody()) ? $psr7->getParsedBody() : null),
            ['scheme' => $uri->getScheme()],
            $factory->normalizeToStringArray($psr7->getCookieParams()),
            $factory->normalizeFiles($psr7->getUploadedFiles()),
            $headers,
            $method,
            is_string($remote) ? $remote : '::1',
            (string) $psr7->getBody()
        );
    }

    /**
     * Join the values of one PSR-7 header into a single comma separated value.
     *
     * @param array<string> $values Header values.
     * @return string The joined header value.
     */
    private function implodeHeaderValues(array $values): string
    {
        return implode(', ', $values);
    }

    /**
     * Create a Request object from PHP global variables.
     *
     * @return Request Returns a Request object initialized with query, post, cookies, files, headers,
     *         method, client IP, and raw body.
     */
    public function getFromGlobal(): Request
    {
        $url = $_SERVER['REQUEST_URI'] ?? null;

        return new Request(
            is_string($url) ? $url : '/',
            $this->normalizeToStringArray($_GET),
            $this->normalizeToStringArray($_POST),
            [],
            $this->normalizeToStringArray($_COOKIE),
            $this->normalizeGlobalFiles($_FILES),
            $this->getHeaders(),
            $this->getMethod() ?? 'GET',
            $this->getClient() ?? '::1',
            $this->getRawBody()
        );
    }

    /**
     * Retrieve all HTTP headers from the current request.
     *
     * @return array<string, string> Returns an associative array of headers with lowercase keys.
     */
    private function getHeaders(): array
    {
        if (function_exists('apache_request_headers')) {
            return $this->lowercaseKeys(apache_request_headers());
        }

        $headers = array_reduce(array_keys($_SERVER), [$this, 'collectServerHeader'], []);

        return $this->lowercaseKeys($this->resolveAuthorization($headers));
    }

    /**
     * Collect one header from the `$_SERVER` superglobal.
     *
     * @param array<string, string> $headers Headers collected so far.
     * @param int|string $key The `$_SERVER` key to inspect.
     * @return array<string, string> The headers with the collected entry added, if valid.
     */
    private function collectServerHeader(array $headers, int|string $key): array
    {
        $value = $_SERVER[$key] ?? null;

        if (!is_string($value)) {
            return $headers;
        }

        $name = (string) $key;

        if (strncmp($name, 'HTTP_', 5) === 0) {
            $name = substr($name, 5);
        } elseif (strncmp($name, 'CONTENT_', 8)) {
            return $headers;
        }

        $headers[strtr($name, '_', '-')] = $value;

        return $headers;
    }

    /**
     * Add the Authorization header from the available server credentials.
     *
     * @param array<string, string> $headers Headers collected from `$_SERVER`.
     * @return array<string, string> The headers with the Authorization fallback applied.
     */
    private function resolveAuthorization(array $headers): array
    {
        if (isset($headers['Authorization'])) {
            return $headers;
        }

        $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;

        if (is_string($authorization)) {
            $headers['Authorization'] = $authorization;

            return $headers;
        }

        if (isset($_SERVER['PHP_AUTH_USER'])) {
            $user = $_SERVER['PHP_AUTH_USER'];
            $pass = $_SERVER['PHP_AUTH_PW'] ?? '';

            if (is_string($user) && is_string($pass)) {
                $headers['Authorization'] = 'Basic ' . base64_encode($user . ':' . $pass);
            }

            return $headers;
        }

        if (isset($_SERVER['PHP_AUTH_DIGEST']) && is_string($_SERVER['PHP_AUTH_DIGEST'])) {
            $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
        }

        return $headers;
    }

    /**
     * Lowercase all header keys.
     *
     * @param array<mixed, mixed> $headers Headers with arbitrary case keys.
     * @return array<string, string> The same headers with lowercase keys.
     */
    private function lowercaseKeys(array $headers): array
    {
        $filtered = array_filter($headers, 'is_string');

        return array_change_key_case(array_filter($filtered, 'is_string', ARRAY_FILTER_USE_KEY));
    }

    /**
     * Get the HTTP request method.
     *
     * This method also supports method overriding using the "X-HTTP-Method-Override" header.
     *
     * @return string|null Returns the HTTP method (e.g., GET, POST) or null if not available.
     */
    private function getMethod(): ?string
    {
        $method   = $_SERVER['REQUEST_METHOD'] ?? null;
        $override = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;

        if (is_string($override)) {
            $method = $method === 'POST' && preg_match('#^[A-Z]+$#D', $override) ? $override : $method;
        }

        return is_string($method) ? $method : null;
    }

    private function getClient(): ?string
    {
        $address = $_SERVER['REMOTE_ADDR'] ?? null;

        return is_string($address) && $address !== ''
            ? trim($address, '[]')
            : null;
    }

    /**
     * Get the raw request body.
     *
     * @return string|null Returns the raw request body or null if not available.
     */
    private function getRawBody(): ?string
    {
        return file_get_contents('php://input') ?: null;
    }

    /**
     * Normalize a raw superglobal array into an associative string/string map.
     *
     * @param array<mixed, mixed>|null $input Raw superglobal data.
     * @return array<string, string> Returns a filtered map of stringified values.
     */
    private function normalizeToStringArray(?array $input): array
    {
        $values = array_filter($input ?? [], 'is_scalar');

        return array_map('strval', array_filter($values, 'is_string', ARRAY_FILTER_USE_KEY));
    }

    /**
     * Normalize a raw `$_FILES` superglobal into the internal uploaded file map.
     *
     * @param array<mixed, mixed>|null $files Raw uploaded file data.
     * @return array<string, array<string, string|int|array<int, string>|array<int, int>>>
     *         A map keyed by file input name.
     */
    private function normalizeGlobalFiles(?array $files): array
    {
        $entries = array_filter($files ?? [], 'is_array');

        return array_map([$this, 'normalizeFileEntry'], array_filter($entries, 'is_string', ARRAY_FILTER_USE_KEY));
    }

    /**
     * Normalize a single raw `$_FILES` entry.
     *
     * A single entry may hold one file (scalar fields) or multiple files
     * (arrays of fields) when the input name uses `[]`.
     *
     * @param array<mixed, mixed> $file The raw file entry.
     * @return array<string, string|int|array<int, string>|array<int, int>>
     *         A normalized file entry with the "name", "type", "tmp_name",
     *         "error" and "size" fields.
     */
    private function normalizeFileEntry(array $file): array
    {
        $name    = $file['name'] ?? null;
        $type    = $file['type'] ?? null;
        $tmpName = $file['tmp_name'] ?? null;
        $error   = $file['error'] ?? null;
        $size    = $file['size'] ?? null;

        if (is_array($name)) {
            $name = array_values(array_filter($name, 'is_string'));
        } elseif (!is_string($name)) {
            $name = '';
        }

        if (is_array($type)) {
            $type = array_values(array_filter($type, 'is_string'));
        } elseif (!is_string($type)) {
            $type = '';
        }

        if (is_array($tmpName)) {
            $tmpName = array_values(array_filter($tmpName, 'is_string'));
        } elseif (!is_string($tmpName)) {
            $tmpName = '';
        }

        if (is_array($error)) {
            $error = array_values(array_filter($error, 'is_int'));
        } elseif (!is_int($error)) {
            $error = 0;
        }

        if (is_array($size)) {
            $size = array_values(array_filter($size, 'is_int'));
        } elseif (!is_int($size)) {
            $size = 0;
        }

        return [
            'name'     => $name,
            'type'     => $type,
            'tmp_name' => $tmpName,
            'error'    => $error,
            'size'     => $size,
        ];
    }

    /**
     * Convert PSR-7 uploaded files into `$_FILES`-style arrays.
     *
     * @param array<mixed, mixed> $files The PSR-7 uploaded file tree.
     * @return array<string, array<string, string|int|array<int, string>|array<int, int>>>
     *         The normalized uploaded file tree.
     */
    private function normalizeFiles(array $files): array
    {
        $normalized = array_map([$this, 'normalizeFileValue'], $files);
        $normalized = array_filter($normalized, 'is_array');

        return array_filter($normalized, 'is_string', ARRAY_FILTER_USE_KEY);
    }

    /**
     * Normalize a single PSR-7 uploaded file entry.
     *
     * @return array<string, string|int|array<int, string>|array<int, int>>|null
     *         The normalized entry, or null when the value is not an uploaded file.
     */
    private function normalizeFileValue(mixed $value): ?array
    {
        if ($value instanceof UploadedFileInterface) {
            $uri = $value->getStream()->getMetadata('uri');

            return [
                'name'     => $value->getClientFilename() ?? '',
                'type'     => $value->getClientMediaType() ?? '',
                'tmp_name' => is_string($uri) ? $uri : '',
                'error'    => $value->getError(),
                'size'     => $value->getSize() ?? 0,
            ];
        }

        if (is_array($value)) {
            return $this->normalizeFileList($value);
        }

        return null;
    }

    /**
     * Convert a list of PSR-7 uploaded files into a single multi-file entry.
     *
     * @param array<mixed, mixed> $files List or map of uploaded files.
     * @return array<string, array<int, string|int>>
     *         A normalized multi-file entry.
     */
    private function normalizeFileList(array $files): array
    {
        return array_reduce($files, [$this, 'appendUploadedFile'], [
            'name'     => [],
            'type'     => [],
            'tmp_name' => [],
            'error'    => [],
            'size'     => [],
        ]);
    }

    /**
     * Append one uploaded file to a multi-file accumulator.
     *
     * @param array<string, array<int, string|int>> $carry Accumulated fields.
     * @param mixed $file A possible uploaded file.
     * @return array<string, array<int, string|int>> The updated accumulator.
     */
    private function appendUploadedFile(array $carry, mixed $file): array
    {
        if (!$file instanceof UploadedFileInterface) {
            return $carry;
        }

        $uri = $file->getStream()->getMetadata('uri');

        $carry['name'][]     = $file->getClientFilename() ?? '';
        $carry['type'][]     = $file->getClientMediaType() ?? '';
        $carry['tmp_name'][] = is_string($uri) ? $uri : '';
        $carry['error'][]    = $file->getError();
        $carry['size'][]     = $file->getSize() ?? 0;

        return $carry;
    }
}
