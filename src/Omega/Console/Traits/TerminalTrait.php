<?php

/**
 * Part of Omega - Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Console\Traits;

use function array_key_exists;
use function count;
use function explode;
use function function_exists;
use function getenv;
use function in_array;
use function preg_match;
use function sapi_windows_vt100_support;
use function shell_exec;
use function stream_isatty;
use function strtoupper;
use function trim;

use const DIRECTORY_SEPARATOR;
use const PHP_OS_FAMILY;
use const STDOUT;

/**
 * TerminalTrait provides helper methods to retrieve terminal dimensions,
 * ensuring values remain within a specified min/max range.
 *
 * @category   Omega
 * @package    Console
 * @subpackges Traits
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
trait TerminalTrait
{
    /**
     * Get the terminal width (number of columns).
     *
     * The method first checks for a custom environment variable `TERMINAL_COLUMNS`,
     * then `$_ENV['COLUMNS']`. If not available, it tries OS-specific commands:
     * - On Windows: `mode con`
     * - On Unix-like: `stty size`
     * If all else fails, it returns the specified minimum width.
     *
     * The returned width is always clamped between `$min` and `$max`.
     *
     * @param int $min Minimum allowed terminal width (default 80)
     * @param int $max Maximum allowed terminal width (default 160)
     * @return int The terminal width, constrained between `$min` and `$max`
     */
    protected function getWidth(int $min = 80, int $max = 160): int
    {
        $custom = env('TERMINAL_COLUMNS');
        if ($custom !== false) {
            return $this->minMax((int) trim((string) $custom), $min, $max);
        }

        if (array_key_exists('COLUMNS', $_ENV)) {
            return $this->minMax((int) trim((string) $_ENV['COLUMNS']), $min, $max);
        }

        if (!function_exists('shell_exec')) {
            return $min;
        }

        if ('Windows' === PHP_OS_FAMILY) {
            $modeOutput = shell_exec('mode con');
            if (preg_match('/Columns:\s+(\d+)/', $modeOutput, $matches)) {
                return $this->minMax((int) $matches[1], $min, $max);
            }

            return $min;
        }

        $sttyOutput = shell_exec('stty size 2>&1');
        if ($sttyOutput) {
            $dimensions = explode(' ', trim($sttyOutput));
            if (2 === count($dimensions)) {
                return $this->minMax((int) $dimensions[1], $min, $max);
            }
        }

        return $min;
    }

    /**
     * Ensure a value is within a specified minimum and maximum range.
     *
     * @param int $value Value to clamp
     * @param int $min Minimum allowed value
     * @param int $max Maximum allowed value
     * @return int The clamped value
     */
    private function minMax(int $value, int $min, int $max): int
    {
        return $value < $min ? $min : (min($value, $max));
    }


    /**
     * @param resource $stream
     */
    protected function hasColorSupport(mixed $stream = STDOUT): bool
    {
        if ('' !== (($_SERVER['NO_COLOR'] ?? getenv('NO_COLOR'))[0] ?? '')) {
            return false;
        }

        if (
            !@stream_isatty($stream)
            && !in_array(strtoupper((string) getenv('MSYSTEM')), ['MINGW32', 'MINGW64'], true)
        ) {
            return false;
        }

        if ('\\' === DIRECTORY_SEPARATOR && @sapi_windows_vt100_support($stream)) {
            return true;
        }

        if (
            'Hyper' === getenv('TERM_PROGRAM')
            || false !== getenv('COLORTERM')
            || false !== getenv('ANSICON')
            || 'ON' === getenv('ConEmuANSI')
        ) {
            return true;
        }

        if ('dumb' === $term = (string) getenv('TERM')) {
            return false;
        }

        return 1 === preg_match(
            '/^((screen|xterm|vt100|vt220|putty|rxvt|ansi|cygwin|linux).*)|(.*-256(color)?(-bce)?)$/',
            $term
        );
    }
}
