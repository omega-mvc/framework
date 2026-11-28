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

use function array_diff_key;
use function array_filter;
use function array_flip;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_reduce;
use function array_replace;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_int;
use function is_string;
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
        $items = array_replace($items, array_filter($value, 'is_string', ARRAY_FILTER_USE_KEY));
        $items = array_merge($items, array_filter($value, 'is_int', ARRAY_FILTER_USE_KEY));

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
        $items    = $this->parseDirective($header);
        $newItems = array_diff_key($items, [$item => true]);
        $newItems = array_diff_key($newItems, array_flip(array_keys($newItems, $item, true)));

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

        if (array_key_exists($item, $items)) {
            return true;
        }

        return in_array($item, $items, true);
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

        $header = $this->get($key);

        if (!is_string($header)) {
            return [];
        }

        $pattern = '/,\s*(?=(?:[^\"]*\"[^\"]*\")*[^\"]*$)/';

        /** @var list<string> $parts */
        $parts = preg_split($pattern, $header);

        return array_reduce($parts, [$this, 'appendDirective'], []);
    }

    /**
     * Append a single directive item to the parsed result.
     *
     * @param array<string|int, string|string[]> $result Parsed directives so far.
     * @param string                             $item   Raw directive item.
     * @return array<string|int, string|string[]> Updated directives.
     */
    private function appendDirective(array $result, string $item): array
    {
        if (!str_contains($item, '=')) {
            $result[] = $item;

            return $result;
        }

        [$name, $value] = explode('=', $item, 2);
        $result[trim($name)] = $this->directiveValue(trim($value));

        return $result;
    }

    /**
     * Normalize a directive value, expanding quoted lists when present.
     *
     * @param string $value Raw directive value.
     * @return string|array<string> Normalized value.
     */
    private function directiveValue(string $value): string|array
    {
        if (!str_starts_with($value, '"')) {
            return $value;
        }
        if (!str_ends_with($value, '"')) {
            return $value;
        }

        return array_map('trim', explode(', ', substr($value, 1, -1)));
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
        $encoded = array_map([$this, 'encodeDirective'], array_keys($data), $data);

        return rtrim(implode(', ', $encoded) . ', ', ', ');
    }

    /**
     * Encode a single directive entry.
     *
     * @param int|string      $key   Directive key.
     * @param string|string[] $value Directive value.
     * @return string Encoded directive entry.
     */
    private function encodeDirective(int|string $key, string|array $value): string
    {
        $value = $this->encodeValue($value);

        if (is_int($key)) {
            return $value;
        }

        return $key . '=' . $value;
    }

    /**
     * Encode a directive value, quoting array values.
     *
     * @param string|string[] $value Directive value.
     * @return string Encoded value.
     */
    private function encodeValue(string|array $value): string
    {
        if (is_array($value)) {
            return '"' . implode(', ', $value) . '"';
        }

        return $value;
    }
}
