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

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Terminal;

use function array_reduce;
use function max;
use function str_repeat;

/**
 * Provides helper methods for rendering aligned console output.
 *
 * This trait contains reusable utilities for measuring the visible width
 * of formatted console strings and producing consistently aligned output
 * regardless of ANSI decorations or terminal size.
 *
 * It is intended for console commands and output components that require
 * right-aligned messages or width-aware formatting.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Traits
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
trait InteractsWithConsoleOutputTrait
{
    /**
     * Cached terminal width.
     *
     * The width is resolved only once and reused for subsequent output
     * operations during the current command execution.
     *
     * @var int|null
     */
    private ?int $terminalWidth = null;

    /**
     * Returns the current terminal width.
     *
     * The value is cached after the first lookup to avoid repeatedly
     * querying the terminal dimensions.
     *
     * @return int The terminal width in characters.
     */
    protected function getTerminalWidth(): int
    {
        return $this->terminalWidth ??= new Terminal()->getWidth();
    }

    /**
     * Returns the visible width of a formatted string.
     *
     * ANSI formatting tags and terminal decorations are removed before
     * calculating the string length, ensuring the returned value reflects
     * only the characters actually displayed.
     *
     * @param string $string The formatted string.
     * @return int The visible width of the string.
     */
    protected function getVisibleWidth(string $string): int
    {
        return Helper::width(
            Helper::removeDecoration(
                $this->output->getFormatter(),
                $string
            )
        );
    }

    /**
     * Returns the largest visible width from a collection of values.
     *
     * Each item is converted to a string before its visible width is
     * calculated.
     *
     * @param array<int|string, string> $items The values to measure.
     * @return int The maximum visible width, or zero if the array is empty.
     */
    protected function getVisibleMaxWidth(array $items): int
    {
        if (empty($items)) {
            return 0;
        }

        return array_reduce($items, function (int $max, string $item): int {
            return max($max, $this->getVisibleWidth($item));
        }, 0);
    }

    /**
     * Writes a message aligned to the right side of the terminal.
     *
     * The alignment is based on the visible width of the message, ignoring
     * any formatting tags or ANSI escape sequences.
     *
     * @param string $message The message to display.
     * @param int $margin Number of spaces to leave between the message and
     *                    the right edge of the terminal.
     * @return void
     */
    protected function writeRight(string $message, int $margin = 2): void
    {
        $visualWidth = $this->getVisibleWidth($message);

        $width = $this->getTerminalWidth();

        $spacesCount = max(0, $width - $visualWidth - $margin);

        $this->output->writeln(
            str_repeat(' ', $spacesCount) . $message
        );
    }
}
