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

use Omega\Macroable\MacroableTrait;
use Omega\Text\Exceptions\NoReturnException;

use function array_keys;
use function array_map;
use function array_pop;
use function array_values;
use function explode;
use function iconv;
use function implode;
use function is_string;
use function mb_strpos;
use function mb_strtolower;
use function mb_strtoupper;
use function mb_substr;
use function preg_match;
use function preg_replace;
use function preg_split;
use function str_pad;
use function str_repeat;
use function str_replace;
use function strlen;
use function strncmp;
use function substr_compare;
use function trim;
use function ucfirst;
use function ucwords;

use const PHP_INT_MAX;
use const PREG_SPLIT_NO_EMPTY;
use const STR_PAD_LEFT;

/**
 * Final utility class providing a rich set of string manipulation methods.
 *
 * This class offers static methods for common string operations including:
 *   - Creating Text instances
 *   - Character access
 *   - Searching, replacing, and splitting strings
 *   - Case transformations (lower, upper, camel, pascal, snake, kebab)
 *   - String padding and masking
 *   - Template rendering and slug generation
 *   - Validation and checking (isString, isEmpty, contains, startsWith, endsWith)
 *
 * All methods are static, and the class is designed to complement the Text class.
 *
 * @category  Omega
 * @package   Text
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 *
 * @method static addPrefix(string $string, string $string1)
 * @method static hay()
 */
final class Str
{
    use MacroableTrait;

    /**
     * Create a new Text instance from a string.
     *
     * @param string $text Input text
     * @return Text
     */
    public static function of(string $text): Text
    {
        return new Text($text);
    }

    /**
     * Get the character at the specified index in a string.
     *
     * @param string $text  The string to search
     * @param int    $index Character index
     * @return string|false Returns the character or false if index invalid
     */
    public static function charAt(string $text, int $index): string|false
    {
        return mb_substr($text, $index, 1);
    }

    /**
     * Concatenate multiple strings with optional separators.
     *
     * @param string[] $text Array of strings
     * @param string   $separator Separator between strings
     * @param string   $lastSeparator Separator before last element
     * @return string Concatenated string
     */
    public static function concat(array $text, string $separator = ' ', string $lastSeparator = ''): string
    {
        if ('' !== $lastSeparator) {
            $removeLast = array_pop($text);
            $text[]     = $lastSeparator;
            $text[]     = $removeLast;
        }

        return implode($separator, $text);
    }

    /**
     * Get the index of the first occurrence of a substring.
     *
     * @param string $text  String to search
     * @param string $find  Substring to find
     * @return int|false Position of first occurrence or false if not found
     */
    public static function indexOf(string $text, string $find): int|false
    {
        return mb_strpos($text, $find, 1);
    }

    /**
     * Get the last index of a substring within a string.
     *
     * @param string $text String to search
     * @param string $find Substring to find
     * @return int|false Last occurrence position or false if not found
     */
    public static function lastIndexOf(string $text, string $find): int|false
    {
        return mb_strpos($text, $find, -1);
    }

    /**
     * Match a string against a regular expression pattern.
     *
     * @param string $text    Input string
     * @param string $pattern Regex pattern
     * @return array<int, string>|null Array of matches or null if none
     */
    public static function match(string $text, string $pattern): ?array
    {
        $matches    = [];
        $hasResult = preg_match($pattern, $text, $matches);

        if (1 === $hasResult) {
            return $matches;
        }

        return null;
    }

    /**
     * Replace occurrences of a string or array of strings.
     *
     * @param string|string[] $find    String(s) to find
     * @param string|string[] $replace Replacement string(s)
     * @param string          $original Original string
     * @return string Modified string
     */
    public static function replace(string $original, string|array $find, string|array $replace): string
    {
        return str_replace($find, $replace, $original);
    }

    /**
     * Find the first occurrence of a substring and return its position.
     *
     * @param string $text The string to search within.
     * @param string $find The substring to locate.
     * @return int|false Returns the position of the first match or false if not found.
     */
    public static function search(string $text, string $find): int|false
    {
        return mb_strpos($text, $find);
    }

    /**
     * Slice a portion of a string from start with optional length.
     *
     * @param string   $text   Input string
     * @param int      $start  Start position
     * @param int|null $length Optional length of slice
     * @return string|false Substring or false on failure
     */
    public static function slice(string $text, int $start, ?int $length = null): string|false
    {
        $textLength = $length ?? self::length($text);

        return mb_substr($text, $start, $textLength);
    }

    /**
     * Splits a string into an array of substrings.
     *
     * This method splits the input string `$text` using the specified `$separator`.
     * If the `$separator` is an empty string, the string is split into individual characters.
     * Otherwise, it behaves like `explode()`. The `$limit` parameter controls the maximum
     * number of elements in the resulting array.
     *
     * @param string $text      The input string to split.
     * @param string $separator The delimiter to split by. Default is empty string (splits into characters).
     * @param int    $limit     Maximum number of elements in the returned array. Default is PHP_INT_MAX.
     * @return string[]|false An array of substrings if successful, or false on failure.
     */
    public static function split(string $text, string $separator = '', int $limit = PHP_INT_MAX): array|false
    {
        return '' === $separator
            ? preg_split('//', $text, -1, PREG_SPLIT_NO_EMPTY)
            : explode($separator, $text, $limit);
    }

    /**
     * Convert a string to lowercase.
     *
     * @param string $text Input string
     * @return string Lowercased string
     */
    public static function toLowerCase(string $text): string
    {
        return mb_strtolower($text);
    }

    /**
     * Convert a string to uppercase.
     *
     * @param string $text Input string
     * @return string Uppercased string
     */
    public static function toUpperCase(string $text): string
    {
        return mb_strtoupper($text);
    }

    /**
     * Return the substring after the first occurrence of the specified text.
     *
     * @param string $text The original string.
     * @param string $find The substring to search for.
     * @return string The portion of the string after the first occurrence of $find.
     */
    public static function after(string $text, string $find): string
    {
        $length = strlen($find);
        if (false === ($pos = Str::indexOf($text, $find))) {
            return $text;
        }

        return mb_substr($text, $pos + $length);
    }

    /**
     * Convert the first character of the string to uppercase.
     *
     * @param string $text Input string.
     * @return string String with first character in uppercase.
     */
    public static function firstUpper(string $text): string
    {
        return ucfirst($text);
    }

    /**
     * Convert the first character of each word in the string to uppercase.
     *
     * @param string $text Input string.
     * @return string String with each word starting with an uppercase character.
     */
    public static function firstUpperAll(string $text): string
    {
        return ucwords($text);
    }

    /**
     * Convert the string to snake_case (words separated by underscores).
     *
     * @param string $text Input string.
     * @return string Converted string in snake_case.
     */
    public static function toSnakeCase(string $text): string
    {
        return str_replace([' ', '-', '_', '+'], '_', $text);
    }

    /**
     * Convert the string to kebab-case (words separated by dashes).
     *
     * @param string $text Input string.
     * @return string Converted string in kebab-case.
     */
    public static function toKebabCase(string $text): string
    {
        return str_replace([' ', '-', '_', '+'], '-', $text);
    }

    /**
     * Convert the string to PascalCase (each word starts with uppercase, no separators).
     *
     * @param string $text Input string.
     * @return string Converted string in PascalCase.
     */
    public static function toPascalCase(string $text): string
    {
        $spaceCase  = str_replace(['-', '_', '+'], ' ', $text);
        $firstUpper = Str::firstUpperAll($spaceCase);

        return str_replace(' ', '', $firstUpper);
    }

    /**
     * Convert the string to camelCase (first word lowercase, subsequent words start with uppercase).
     *
     * @param string $text Input string.
     * @return string Converted string in camelCase.
     */
    public static function toCamelCase(string $text): string
    {
        $spaceCase  = str_replace(['-', '_', '+'], ' ', $text);
        $arrText    = explode(' ', $spaceCase);
        $result     = [];
        $firstText  = true;

        foreach ($arrText as $text) {
            if ($firstText) {
                $result[]   = mb_strtolower($text);
                $firstText = false;
                continue;
            }

            $result[] = ucfirst($text);
        }

        return implode('', $result);
    }

    /**
    /**
     * Generate a URL-friendly "slug" from a string.
     *
     * @param string $text Input string
     * @return string Slugified string
     * @throws NoReturnException if the resulting slug is empty
     */
    public static function slug(string $text): string
    {
        $original = $text;

        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = mb_strtolower($text);

        if (empty($text)) {
            throw new NoReturnException(__FUNCTION__, $original);
        }

        return $text;
    }

    /**
     * Repeat a string multiple times.
     *
     * @param string $text     Text to repeat
     * @param int    $multiple Number of repetitions
     * @return string Repeated string
     */
    public static function repeat(string $text, int $multiple): string
    {
        return str_repeat($text, $multiple);
    }

    /**
     * Get the length of a string.
     *
     * @param string $text Input string
     * @return int Length of string
     */
    public static function length(string $text): int
    {
        return strlen($text);
    }

    /**
    /**
     * Render a template string by replacing placeholders with provided data.
     *
     * @param string                $template       Template string
     * @param array<string, string> $data           Replacement data
     * @param string                $openDelimiter  Opening delimiter
     * @param string                $closeDelimiter Closing delimiter
     * @return string Rendered string
     */
    public static function template(
        string $template,
        array $data,
        string $openDelimiter = '{',
        string $closeDelimiter = '}'
    ): string {
        if ('{' === $openDelimiter && '}' === $closeDelimiter) {
            $template = preg_replace(['/\\{\s+/', '/\s+\\}/'], ['{', '}'], $template);
        }

        $keys = [];
        foreach ($data as $key => $value) {
            $keys[] = $openDelimiter . $key . $closeDelimiter;
        }

        return str_replace($keys, $data, $template);
    }

    /**
     * Fill a string to a maximum length at the start.
     *
     * @param string $text      Input string
     * @param string $fill      Fill character
     * @param int    $maxLength Desired length
     * @return string Padded string
     */
    public static function fill(string $text, string $fill, int $maxLength): string
    {
        return str_pad($text, $maxLength, $fill, STR_PAD_LEFT);
    }

    /**
     * Fill a string to a maximum length at the end.
     *
     * @param string $text      Input string
     * @param string $fill      Fill character
     * @param int    $maxLength Desired length
     * @return string Padded string
     */
    public static function fillEnd(string $text, string $fill, int $maxLength): string
    {
        // Uses the native str_pad function's default value STR_PAD_RIGHT
        return str_pad($text, $maxLength, $fill);
    }

    /**
     * Mask part of a string with a given character.
     *
     * @param string $text       Input string
     * @param string $mask       Mask character
     * @param int    $start      Start index
     * @param int    $maskLength Length of mask
     * @return string Masked string
     */
    public static function mask(string $text, string $mask, int $start, int $maskLength = 9999): string
    {
        // negative position, count from end text
        if ($start < 0) {
            $start = strlen($text) + $start;
        }

        $end = $start + $maskLength;
        $start--;
        $arrText = preg_split('//', $text, -1, PREG_SPLIT_NO_EMPTY);
        $newText = array_map(function ($index, $string) use ($mask, $start, $end) {
            if ($index > $start && $index < $end) {
                return $mask;
            }

            return $string;
        }, array_keys($arrText), array_values($arrText));

        return implode('', $newText);
    }

    /**
     * Check if input is a string.
     *
     * @param string $text Input
     * @return bool True if string
     */
    public static function isString(string $text): bool
    {
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        return is_string($text);
    }

    /**
     * Check if string is empty.
     *
     * @param string $text Input
     * @return bool True if empty
     */
    public static function isEmpty(string $text): bool
    {
        return '' === $text;
    }

    /**
    /**
     * Check if the string matches a given regular expression pattern.
     *
     * @param string $text    The string to test against the pattern.
     * @param string $pattern The regular expression pattern to match.
     * @return bool True if the pattern matches the string, false otherwise.
     */
    public static function isMatch(string $text, string $pattern): bool
    {
        $hasResult = preg_match($pattern, $text);

        return 1 === $hasResult;
    }

    /**
     * Shorthand method for `isMatch()`. Checks if the string matches a pattern.
     *
     * @param string $text    The string to test.
     * @param string $pattern The regular expression pattern to match.
     * @return bool True if the pattern matches the string, false otherwise.
     */
    public static function is(string $text, string $pattern): bool
    {
        return Str::isMatch($text, $pattern);
    }

    // Backward Compatible php 8.0 --------------------------------

    /**
     * Check if string contains a substring.
     *
     * @param string $text Text to search
     * @param string $find Substring
     * @return bool True if contains
     */
    public static function contains(string $text, string $find): bool
    {
        return '' === $find || false !== mb_strpos($text, $find);
    }

    /**
     * Check if string starts with given substring.
     *
     * @param string $text      Input string
     * @param string $startWith Substring
     * @return bool True if starts with
     */
    public static function startsWith(string $text, string $startWith): bool
    {
        return 0 === strncmp($text, $startWith, strlen($startWith));
    }

    /**
     * Check if string ends with given substring.
     *
     * @param string $text       Input string
     * @param string $startWith  Substring
     * @return bool True if ends with
     */
    public static function endsWith(string $text, string $startWith): bool
    {
        if ('' === $startWith || $startWith === $text) {
            return true;
        }

        if ('' === $text) {
            return false;
        }

        $needleLength = strlen($startWith);

        return $needleLength <= strlen($text) && 0 === substr_compare($text, $startWith, -$needleLength);
    }

    /**
     * Limit string to a maximum length and append a truncation character.
     *
     * @param string $text              Input string
     * @param int    $length            Maximum length
     * @param string $truncateCharacter Character to append
     * @return string Truncated string
     */
    public static function limit(string $text, int $length, string $truncateCharacter = '...'): string
    {
        return self::slice($text, 0, $length) . $truncateCharacter;
    }
}
