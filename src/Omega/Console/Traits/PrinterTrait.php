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

use Omega\Console\Style\Decorate;

use function chr;
use function implode;
use function str_repeat;

/**
 * PrinterTrait provides low-level utilities to handle ANSI escape codes
 * for text styling, line manipulation, and console output formatting.
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
trait PrinterTrait
{
    /**
     * Apply multiple ANSI codes to a text string.
     *
     * @param array<int, string|int> $rule ANSI codes to apply
     * @param int|string             $text Text to style
     * @param bool                   $reset Whether to reset formatting after text
     * @param array<int, string|int> $resetRule ANSI codes to reset
     * @return string Formatted text with applied rules
     */
    protected function rules(
        array $rule,
        int|string $text,
        bool $reset = true,
        array $resetRule = [Decorate::RESET]
    ): string {
        if ([] === $rule && [] === $resetRule) {
            return (string) $text;
        }

        $stringRules      = implode(';', $rule);
        $stringResetRules = implode(';', $resetRule);

        return $this->rule($stringRules, $text, $reset, $stringResetRules);
    }

    /**
     * Apply a single ANSI code to a text string.
     *
     * @param int|string $rule ANSI code to apply
     * @param int|string $text Text to style
     * @param bool       $reset Whether to reset formatting after text
     * @param int|string $resetRule ANSI code to reset
     * @return string Formatted text with applied rule
     */
    protected function rule(
        int|string $rule,
        int|string $text,
        bool $reset = true,
        int|string $resetRule = Decorate::RESET
    ): string {
        $rule      = chr(27) . '[' . $rule . 'm' . $text;
        $resetRule = chr(27) . '[' . $resetRule . 'm';

        return $reset
            ? $rule . $resetRule
            : $rule;
    }

    /**
     * Generate new line characters.
     *
     * @param int $count Number of new lines
     * @return string New line characters
     */
    protected function newLine(int $count = 1): string
    {
        return str_repeat("\n", $count);
    }

    /**
     * Generate tab characters.
     *
     * @param int $count Number of tabs
     * @return string Tab characters
     */
    protected function tabs(int $count = 1): string
    {
        return str_repeat("\t", $count);
    }

    /**
     * Replace the content of a specific console line with new text.
     *
     * @param string $replace Text to replace the line with
     * @param int    $line Line offset (-1 for current line)
     * @return void
     */
    protected function replaceLine(string $replace, int $line = -1): void
    {
        $this->moveLine($line);
        echo chr(27) . "[K\r" . $replace;
    }

    /**
     * Clear a specific console line.
     *
     * @param int $line Line offset (-1 for current line)
     * @return void
     */
    protected function clearLine(int $line = -1): void
    {
        $this->moveLine($line);
        $this->replaceLine('');
    }

    /**
     * Move the cursor vertically by a given number of lines.
     *
     * Positive values move the cursor up.
     *
     * @param int $line Number of lines to move
     * @return void
     */
    protected function moveLine(int $line): void
    {
        echo chr(27) . "[{$line}A";
    }
}
