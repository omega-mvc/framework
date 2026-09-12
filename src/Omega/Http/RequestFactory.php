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
use function array_filter;
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
use function strtolower;
use function strtr;
use function substr;
use function trim;

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
        $factory = new self();

        $headers = [];
        foreach ($psr7->getHeaders() as $name => $values) {
            $headers[strtolower($name)] = implode(', ', $values);
        }

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

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (!is_string($value)) {
                continue;
            }

            if (strncmp($key, 'HTTP_', 5) === 0) {
                $key = substr($key, 5);
            } elseif (strncmp($key, 'CONTENT_', 8)) {
                continue;
            }

            $headers[strtr($key, '_', '-')] = $value;
        }

        if (!isset($headers['Authorization'])) {
            $authorization = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? null;

            if (is_string($authorization)) {
                $headers['Authorization'] = $authorization;
            } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
                $user = $_SERVER['PHP_AUTH_USER'];
                $pass = $_SERVER['PHP_AUTH_PW'] ?? '';

                if (is_string($user) && is_string($pass)) {
                    $headers['Authorization'] = 'Basic ' . base64_encode($user . ':' . $pass);
                }
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST']) && is_string($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }

        return $this->lowercaseKeys($headers);
    }

    /**
     * Lowercase all header keys.
     *
     * @param array<mixed, mixed> $headers Headers with arbitrary case keys.
     * @return array<string, string> The same headers with lowercase keys.
     */
    private function lowercaseKeys(array $headers): array
    {
        $lowercased = [];

        foreach ($headers as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $lowercased[strtolower($key)] = $value;
            }
        }

        return $lowercased;
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
        $normalized = [];

        foreach ($input ?? [] as $key => $value) {
            if (is_string($key) && is_scalar($value)) {
                $normalized[$key] = (string) $value;
            }
        }

        return $normalized;
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
        $normalized = [];

        foreach ($files ?? [] as $key => $value) {
            if (!is_string($key) || !is_array($value)) {
                continue;
            }

            $normalized[$key] = $this->normalizeFileEntry($value);
        }

        return $normalized;
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
        $normalized = [];

        foreach ($files as $key => $value) {
            if (!is_string($key)) {
                continue;
            }

            if ($value instanceof UploadedFileInterface) {
                $uri = $value->getStream()->getMetadata('uri');

                $normalized[$key] = [
                    'name'     => $value->getClientFilename() ?? '',
                    'type'     => $value->getClientMediaType() ?? '',
                    'tmp_name' => is_string($uri) ? $uri : '',
                    'error'    => $value->getError(),
                    'size'     => $value->getSize() ?? 0,
                ];
            } elseif (is_array($value)) {
                $normalized[$key] = $this->normalizeFileList($value);
            }
        }

        return $normalized;
    }

    /**
     * Convert a list of PSR-7 uploaded files into a single multi-file entry.
     *
     * @param array<mixed, mixed> $files List or map of uploaded files.
     * @return array<string, string|int|array<int, string>|array<int, int>>
     *         A normalized multi-file entry.
     */
    private function normalizeFileList(array $files): array
    {
        $names    = [];
        $types    = [];
        $tmpNames = [];
        $errors   = [];
        $sizes    = [];

        foreach ($files as $file) {
            if (!$file instanceof UploadedFileInterface) {
                continue;
            }

            $uri = $file->getStream()->getMetadata('uri');

            $names[]    = $file->getClientFilename() ?? '';
            $types[]    = $file->getClientMediaType() ?? '';
            $tmpNames[] = is_string($uri) ? $uri : '';
            $errors[]   = $file->getError();
            $sizes[]    = $file->getSize() ?? 0;
        }

        return [
            'name'     => $names,
            'type'     => $types,
            'tmp_name' => $tmpNames,
            'error'    => $errors,
            'size'     => $sizes,
        ];
    }
}