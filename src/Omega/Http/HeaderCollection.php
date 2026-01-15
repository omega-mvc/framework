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

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Http;

use Exception;
use Omega\Collection\Collection;
use Omega\Text\Str;

use function array_map;
use function explode;
use function implode;
use function is_array;
use function is_int;
use function preg_split;
use function rtrim;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function substr;
use function trim;

/**
 * Collection specialized for managing HTTP headers.
 *
 * This class extends the base {@see Collection} to provide
 * helpers for working with HTTP header fields and directives.
 * It supports parsing, modifying, encoding, and rendering
 * header values that contain multiple directives (e.g.
 * Cache-Control, Content-Disposition).
 *
 * Headers are internally stored as key-value pairs and can
 * be converted to a valid raw HTTP header string.
 *
 * @category  Omega
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 *
 * @extends Collection<string, string>
 */
class HeaderCollection extends Collection
{
    /**
     * Create a new header collection.
     *
     * @param array<string, string> $headers Associative array of header
     *                                       names and their values.
     */
    public function __construct(array $headers)
    {
        parent::__construct($headers);
    }

    /**
     * Convert the header collection to a raw HTTP header string.
     *
     * Each header is formatted as "Header-Name: value" and
     * separated by CRLF characters.
     *
     * @return string Raw HTTP header representation.
     */
    public function __toString(): string
    {
        $headers = $this->clone()->map(fn (string $value, string $key = ''): string => "{$key}: {$value}")->toArray();

        return implode("\r\n", $headers);
    }

    /**
     * Set a raw HTTP header line.
     *
     * The header string must follow the "Header-Name: value" format.
     *
     * @param string $header Raw header string.
     * @return $this
     * @throws Exception If the header structure is invalid.
     */
    public function setRaw(string $header): self
    {
        if (false === Str::contains($header, ':')) {
            throw new Exception("Invalid header structure {$header}.");
        }

        [$headerName, $headerVal] = explode(':', $header, 2);

        return $this->set(trim($headerName), trim($headerVal));
    }

    /**
     * Get the parsed directives of a header.
     *
     * This method parses a header value into an array of
     * directives, handling both key-value pairs and
     * standalone values.
     *
     * @param string $header Header name.
     * @return array<string|int, string|string[]> Parsed header directives.
     */
    public function getDirective(string $header): array
    {
        return $this->parseDirective($header);
    }

    /**
     * Add one or more directives to an existing header.
     *
     * If the header already exists, the new directives are merged
     * with the current ones. Numeric keys are appended, while
     * string keys overwrite existing values.
     *
     * @param string $header Header name.
     * @param array<int|string, string|string[]> $value Directives to add.
     * @return $this
     */
    public function addDirective(string $header, array $value): self
    {
        $items = $this->parseDirective($header);
        foreach ($value as $key => $newItem) {
            if (is_int($key)) {
                $items[] = $newItem;
                continue;
            }
            $items[$key] = $newItem;
        }

        return $this->set($header, $this->encodeToString($items));
    }

    /**
     * Remove a directive or directive value from a header.
     *
     * Both directive keys and values are checked and removed
     * when matched.
     *
     * @param string $header Header name.
     * @param string $item   Directive key or value to remove.
     * @return $this
     */
    public function removeDirective(string $header, string $item): self
    {
        $items     = $this->parseDirective($header);
        $newItems  = [];
        foreach ($items as $key => $value) {
            if ($key === $item) {
                continue;
            }
            if ($value === $item) {
                continue;
            }
            $newItems[$key] = $value;
        }

        return $this->set($header, $this->encodeToString($newItems));
    }

    /**
     * Determine if a header contains a specific directive.
     *
     * The check is performed against both directive keys
     * and directive values.
     *
     * @param string $header Header name.
     * @param string $item   Directive key or value to check.
     * @return bool True if the directive exists, false otherwise.
     */
    public function hasDirective(string $header, string $item): bool
    {
        $items = $this->parseDirective($header);
        foreach ($items as $key => $value) {
            if ($key === $item) {
                return true;
            }
            if ($value === $item) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parse a header value into an array of directives.
     *
     * Supports key-value directives as well as standalone
     * values and quoted multi-value directives.
     *
     * @param string $key Header name.
     * @return array<string|int, string|string[]> Parsed directives.
     */
    private function parseDirective(string $key): array
    {
        if (false === $this->has($key)) {
            return [];
        }

        $header       = $this->get($key);
        $pattern      = '/,\s*(?=(?:[^\"]*\"[^\"]*\")*[^\"]*$)/';
        $headerItem   = preg_split($pattern, $header);

        $result = [];
        foreach ($headerItem as $item) {
            if (str_contains($item, '=')) {
                $parts = explode('=', $item, 2);
                $key   = trim($parts[0]);
                $value = trim($parts[1]);
                if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
                    $value = substr($value, 1, -1);
                    $value = array_map('trim', explode(', ', $value));
                }
                $result[$key] = $value;
                continue;
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * Encode an array of directives into a header string.
     *
     * Array values are automatically quoted and joined
     * according to HTTP header formatting rules.
     *
     * @param array<string|int, string|string[]> $data Parsed directives.
     * @return string Encoded header value.
     */
    private function encodeToString(array $data): string
    {
        $encodedString = '';

        foreach ($data as $key => $value) {
            if (is_int($key)) {
                $encodedString .= $value . ', ';
                continue;
            }

            if (is_array($value)) {
                $value = '"' . implode(', ', $value) . '"';
            }
            $encodedString .= $key . '=' . $value . ', ';
        }

        return rtrim($encodedString, ', ');
    }
}
