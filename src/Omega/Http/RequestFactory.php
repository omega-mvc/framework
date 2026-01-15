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

use function apache_request_headers;
use function array_change_key_case;
use function base64_encode;
use function file_get_contents;
use function function_exists;
use function preg_match;
use function strncmp;
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
     * Create a Request object from PHP global variables.
     *
     * @return Request Returns a Request object initialized with query, post, cookies, files, headers,
     *         method, client IP, and raw body.
     */
    public function getFromGlobal(): Request
    {
        return new Request(
            $_SERVER['REQUEST_URI'] ?? '',
            $_GET,
            $_POST,
            [],
            $_COOKIE,
            $_FILES,
            $this->getHeaders(),
            $this->getMethod(),
            $this->getClient(),
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
            return array_change_key_case(
                apache_request_headers()
            );
        }

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (strncmp($key, 'HTTP_', 5) === 0) {
                $key = substr($key, 5);
            } elseif (strncmp($key, 'CONTENT_', 8)) {
                continue;
            }
            $headers[strtr($key, '_', '-')] = $value;
        }

        if (!isset($headers['Authorization'])) {
            if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
                $basic_pass               = $_SERVER['PHP_AUTH_PW'] ?? '';
                $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }

        return array_change_key_case($headers);
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
        $method = $_SERVER['REQUEST_METHOD'] ?? null;
        if (
            $method === 'POST'
            && preg_match('#^[A-Z]+$#D', $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '')
        ) {
            $method = $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'];
        }

        return $method;
    }

    private function getClient(): ?string
    {
        return !empty($_SERVER['REMOTE_ADDR'])
            ? trim($_SERVER['REMOTE_ADDR'], '[]')
            : null;
    }

    /**
     * Get the client IP address from the request.
     *
     * @return string|null Returns the client IP address or null if not available.
     */
    private function getRawBody(): ?string
    {
        return file_get_contents('php://input') ?: null;
    }
}
