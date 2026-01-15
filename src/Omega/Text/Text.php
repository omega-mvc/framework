<?php

/**
 * Part of Omega - Text Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Text;

use Omega\Text\Exceptions\NoReturnException;

use function gettype;

/**
 * Class Text
 *
 * Provides an object-oriented wrapper for string manipulation.
 * Tracks original string, current string, and modification history.
 * Allows method chaining and optional error handling when string operations fail.
 *
 * @category  Omega
 * @package   Text
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
final class Text
{
    /** @var string Original string input. */
    private string $original;

    /** @var string Current string. */
    private string $current;

    /** @var array<string, array<string, string>> Log of string modifications. */
    private array $latest;

    /** @var bool Throw exception when string method returns false instead of string. */
    private bool $throwOnFailure = false;

    /**
     * Initialize the Text object with an input string.
     *
     * @param string $text Input string
     */
    public function __construct(string $text)
    {
        $this->original = $text;
        $this->execute($text, __FUNCTION__);
    }

    /**
     * Execute a string modification and log it.
     *
     * @param bool|string|array<int|string, string> $text New incoming text
     * @param string                                $functionName Name of the method calling this execution
     * @return string
     * @noinspection PhpReturnValueOfMethodIsNeverUsedInspection
     */
    private function execute(array|bool|string $text, string $functionName): string
    {
        if (Str::isString($text)) {
            $this->current = $text;
        }

        $this->latest[] = [
            'function' => $functionName,
            'return'   => $text,
            'type'     => gettype($text),
        ];

        return $text;
    }

    /**
     * Set a new string value without erasing the modification history.
     *
     * @param string $text New string
     * @return self
     */
    public function text(string $text): self
    {
        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Get the current string.
     *
     * @return string Last/current string
     */
    public function getText(): string
    {
        return $this->current;
    }

    /**
     * Get the current string as string.
     *
     * @return string Last/current string
     */
    public function __toString(): string
    {
        return $this->getText();
    }

    /**
     * Get the modification history of the string.
     *
     * @return array<string, array<string, string>>
     */
    public function logs(): array
    {
        return $this->latest;
    }

    /**
     * Reset the Text object to the original string.
     *
     * @return self
     */
    public function reset(): self
    {
        $this->current = $this->original;
        $this->latest = [];
        $this->throwOnFailure = false;

        return $this;
    }

    /**
     * Refresh the object with a new string and reset history.
     *
     * @param string $text New input string
     * @return self
     */
    public function refresh(string $text): self
    {
        $this->original = $text;

        return $this->reset();
    }

    /**
     * Enable or disable throwing exception when a string operation fails.
     *
     * @param bool $throwError Whether to throw on failure
     * @return self
     */
    public function throwOnFailure(bool $throwError): self
    {
        $this->throwOnFailure = $throwError;

        return $this;
    }

    /**
     * Return the character at the specified index as a new Text instance.
     *
     * @param int $index Character position
     * @return self
     */
    public function charAt(int $index): self
    {
        $text = Str::charAt($this->current, $index);

        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Extract a subsection of the current string.
     *
     * @param int      $start  Start position
     * @param int|null $length Length of the substring, or null to the end
     * @return self
     * @throws NoReturnException if slicing fails and throwOnFailure is enabled
     */
    public function slice(int $start, ?int $length = null): self
    {
        $text = Str::slice($this->current, $start, $length);

        if ($this->throwOnFailure && false === $text) {
            throw new NoReturnException(__FUNCTION__, $this->current);
        }

        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Convert the current string to lowercase.
     *
     * @return self
     */
    public function lower(): self
    {
        $text = Str::toLowerCase($this->current);
        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Convert the current string to uppercase.
     *
     * @return self
     */
    public function upper(): self
    {
        $text = Str::toUpperCase($this->current);
        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Make the first character of the current string uppercase.
     *
     * @return self
     */
    public function firstUpper(): self
    {
        $text = Str::firstUpper($this->current);
        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Capitalize the first character of each word in the current string.
     *
     * @return self
     */
    public function firstUpperAll(): self
    {
        $text = Str::firstUpperAll($this->current);
        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Convert the current string to snake_case.
     *
     * @return self
     */
    public function snake(): self
    {
        $text = Str::toSnakeCase($this->current);
        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Convert the current string to kebab-case.
     *
     * @return self
     */
    public function kebab(): self
    {
        $text = Str::toKebabCase($this->current);
        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Convert the current string to PascalCase.
     *
     * @return self
     */
    public function pascal(): self
    {
        $text = Str::toPascalCase($this->current);
        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Convert the current string to camelCase.
     *
     * @return self
     */
    public function camel(): self
    {
        $text = Str::toCamelCase($this->current);
        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Convert the current string into a slug (kebab-case).
     *
     * @return self
     */
    public function slug(): self
    {
        $text = Str::slug($this->current);
        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Fill the beginning of the string with a given value if it is shorter than the desired length.
     *
     * @param string $fill   String to pad
     * @param int    $length Desired total length of the string
     * @return self
     */
    public function fill(string $fill, int $length): self
    {
        $text = Str::fill($this->current, $fill, $length);
        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Fill the end of the string with a given value if it is shorter than the desired length.
     *
     * @param string $fill   String to pad
     * @param int    $length Desired total length of the string
     * @return self
     */
    public function fillEnd(string $fill, int $length): self
    {
        $text = Str::fillEnd($this->current, $fill, $length);
        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Mask a portion of the string with a specified mask character.
     *
     * @param string $mask       Character(s) to mask with
     * @param int    $start      Start position for masking
     * @param int    $maskLength Length of the mask (default 9999)
     * @return self
     */
    public function mask(string $mask, int $start, int $maskLength = 9999): self
    {
        $text = Str::mask($this->current, $mask, $start, $maskLength);
        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Truncate the string to a specific length, adding an optional truncation character.
     *
     * @param int    $length            Maximum length
     * @param string $truncateCharacter Characters to append when truncated (default '...')
     * @return self
     */
    public function limit(int $length, string $truncateCharacter = '...'): self
    {
        $text = Str::limit($this->current, $length, $truncateCharacter);
        $this->execute($text, __FUNCTION__);

        return $this;
    }

    /**
     * Return the portion of the string after the specified substring.
     *
     * @param string $find Substring to search for
     * @return self
     */
    public function after(string $find): self
    {
        $this->execute(Str::after($this->current, $find), __FUNCTION__);

        return $this;
    }

    /**
     * Get the length of the current string.
     *
     * @return int
     */
    public function length(): int
    {
        return Str::length($this->current);
    }

    /**
     * Get the index of the first occurrence of a substring within the current string.
     *
     * @param string $find Substring to search for
     * @return int|false Position of the substring or false if not found
     */
    public function indexOf(string $find): int|false
    {
        return Str::indexOf($this->current, $find);
    }

    /**
     * Get the index of the last occurrence of a substring within the current string.
     *
     * @param string $find Substring to search for
     * @return int|false Position of the substring or false if not found
     */
    public function lastIndexOf(string $find): int|false
    {
        return Str::lastIndexOf($this->current, $find);
    }

    /**
     * Check if the current string is empty.
     *
     * @return bool True if the string is empty
     */
    public function isEmpty(): bool
    {
        return Str::isEmpty($this->current);
    }

    /**
     * Check if the current string matches a regular expression pattern.
     *
     * @param string $pattern Regular expression to match
     * @return bool True if the string matches the pattern
     */
    public function is(string $pattern): bool
    {
        return Str::isMatch($this->current, $pattern);
    }

    /**
     * Alias for `is()`. Check if the current string matches a regular expression pattern.
     *
     * @param string $pattern Regular expression to match
     * @return bool True if the string matches the pattern
     */
    public function isMatch(string $pattern): bool
    {
        return $this->is($pattern);
    }

    /**
     * Check if the current string contains a given substring.
     *
     * @param string $find Substring to search for
     * @return bool True if the string contains the substring
     */
    public function contains(string $find): bool
    {
        return Str::contains($this->current, $find);
    }

    /**
     * Check if the current string starts with a given substring.
     *
     * @param string $startWith Substring to check at the start
     * @return bool True if the string starts with the substring
     */
    public function startsWith(string $startWith): bool
    {
        return Str::startsWith($this->current, $startWith);
    }

    /**
     * Check if the current string ends with a given substring.
     *
     * @param string $endWith Substring to check at the end
     * @return bool True if the string ends with the substring
     */
    public function endsWith(string $endWith): bool
    {
        return Str::endsWith($this->current, $endWith);
    }
}
