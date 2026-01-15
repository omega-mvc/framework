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

use function array_key_exists;
use function parse_str;
use function parse_url;

/**
 * Class Url
 *
 * Represents a parsed URL and provides convenient access to its components,
 * such as scheme, host, port, user, password, path, query parameters, and fragment.
 *
 * @category  Omega
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Url
{
    /** @var string|null The URL scheme (e.g., "http", "https"). */
    private ?string $schema;

    /**@var string|null The host part of the URL (e.g., "example.com"). */
    private ?string $host;

    /** @var int|null The port number (e.g., 80, 443) if present in the URL. */
    private ?int $port;

    /** @var string|null The username for authentication if present in the URL. */
    private ?string $user;

    /** @var string|null The password for authentication if present in the URL. */
    private ?string $password;

    /** @var string|null The path component of the URL (e.g., "/path/to/resource"). */
    private ?string $path;

    /** @var array<int|string, string>|null The parsed query parameters as key-value pairs. */
    private ?array $query = null;

    /** @var string|null The fragment part of the URL (after the # symbol). */
    private ?string $fragment;

    /**
     * Url constructor.
     *
     * @param array<string, string|int|array<int|string, string>|null> $parseUrl The array returned by parse_url().
     */
    public function __construct(array $parseUrl)
    {
        $this->schema    = $parseUrl['scheme'] ?? null;
        $this->host      = $parseUrl['host'] ?? null;
        $this->port      = $parseUrl['port'] ?? null;
        $this->user      = $parseUrl['user'] ?? null;
        $this->password  = $parseUrl['pass'] ?? null;
        $this->path      = $parseUrl['path'] ?? null;
        $this->fragment  = $parseUrl['fragment'] ?? null;

        if (array_key_exists('query', $parseUrl)) {
            $this->query = $this->parseQuery($parseUrl['query']);
        }
    }

    /**
     * Parse a query string into an associative array.
     *
     * @param string $query The raw query string (e.g., "a=1&b=2").
     * @return array<int|string, string> Parsed key-value pairs.
     */
    private function parseQuery(string $query): array
    {
        $result = [];
        parse_str($query, $result);

        return $result;
    }

    /**
     * Parse a URL string into a Url object.
     *
     * @param string $url The URL to parse.
     * @return self Returns a new Url instance.
     */
    public static function parse(string $url): self
    {
        return new self(parse_url($url));
    }

    /**
     * Create a Url instance from a Request object.
     *
     * @param Request $from The Request instance to extract the URL from.
     * @return self Returns a new Url instance.
     */
    public static function fromRequest(Request $from): self
    {
        return new self(parse_url($from->getUrl()));
    }

    /**
     * Get the URL scheme.
     *
     * @return string|int|array|null Returns the scheme or null if not set.
     */
    public function schema(): array|int|string|null
    {
        return $this->schema;
    }

    /**
     * Get the host component.
     *
     * @return string|int|array|null Returns the host or null if not set.
     */
    public function host(): array|int|string|null
    {
        return $this->host;
    }

    /**
     * Get the port number.
     *
     * @return string|int|array|null Returns the port or null if not set.
     */
    public function port(): array|int|string|null
    {
        return $this->port;
    }

    /**
     * Get the username for authentication.
     *
     * @return string|int|array|null Returns the user or null if not set.
     */
    public function user(): array|int|string|null
    {
        return $this->user;
    }

    /**
     * Get the password for authentication.
     *
     * @return string|int|array|null Returns the password or null if not set.
     */
    public function password(): array|int|string|null
    {
        return $this->password;
    }

    /**
     * Get the path component of the URL.
     *
     * @return string|int|array|null Returns the path or null if not set.
     */
    public function path(): array|int|string|null
    {
        return $this->path;
    }

    /**
     * Get the parsed query parameters.
     *
     * @return array<int|string, string>|null Returns an associative array of query parameters or null if none.
     */
    public function query(): ?array
    {
        return $this->query;
    }

    /**
     * Get the fragment component of the URL.
     *
     * @return string|int|array|null Returns the fragment or null if not set.
     */
    public function fragment(): array|int|string|null
    {
        return $this->fragment;
    }

    /**
     * Check if the URL has a scheme.
     *
     * @return bool Returns true if a scheme is set.
     */
    public function hasSchema(): bool
    {
        return null !== $this->schema;
    }

    /**
     * Check if the URL has a host.
     *
     * @return bool Returns true if a host is set.
     */
    public function hasHost(): bool
    {
        return null !== $this->host;
    }

    /**
     * Check if the URL has a port.
     *
     * @return bool Returns true if a port is set.
     */
    public function hasPort(): bool
    {
        return null !== $this->port;
    }

    /**
     * Check if the URL has a username.
     *
     * @return bool Returns true if a username is set.
     */
    public function hasUser(): bool
    {
        return null !== $this->user;
    }

    /**
     * Check if the URL has a password.
     *
     * @return bool Returns true if a password is set.
     */
    public function hasPassword(): bool
    {
        return null !== $this->password;
    }

    /**
     * Check if the URL has a path.
     *
     * @return bool Returns true if a path is set.
     */
    public function hasPath(): bool
    {
        return null !== $this->path;
    }

    /**
     * Check if the URL has query parameters.
     *
     * @return bool Returns true if query parameters are present.
     */
    public function hasQuery(): bool
    {
        return null !== $this->query;
    }

    /**
     * Check if the URL has a fragment.
     *
     * @return bool Returns true if a fragment is set.
     */
    public function hasFragment(): bool
    {
        return null !== $this->fragment;
    }
}
