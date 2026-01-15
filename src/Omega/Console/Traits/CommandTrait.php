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

use Omega\Console\Style\Color\BackgroundColor;
use Omega\Console\Style\Color\ForegroundColor;
use Omega\Console\Style\Decorate;

/**
 * CommandTrait provides a collection of helper methods to style console output.
 *
 * It includes methods for text colors, background colors, text formatting
 * (bold, underline, blink, reverse, hidden) and supports Just-In-Time
 * foreground and background colors using ForegroundColor and BackgroundColor objects.
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
trait CommandTrait
{
    use PrinterTrait;

    /**
     * Apply red text color.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textRed(string $text): string
    {
        return $this->rule(Decorate::TEXT_RED, $text);
    }

    /**
     * Apply yellow text color.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textYellow(string $text): string
    {
        return $this->rule(Decorate::TEXT_YELLOW, $text);
    }

    /**
     * Apply green text color.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textGreen(string $text): string
    {
        return $this->rule(Decorate::TEXT_GREEN, $text);
    }

    /**
     * Apply blue text color.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textBlue(string $text): string
    {
        return $this->rule(Decorate::TEXT_BLUE, $text);
    }

    /**
     * Apply dim text style.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textDim(string $text): string
    {
        return $this->rule(Decorate::TEXT_DIM, $text);
    }

    /**
     * Apply magenta text color.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textMagenta(string $text): string
    {
        return $this->rule(Decorate::TEXT_MAGENTA, $text);
    }

    /**
     * Apply cyan text color.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textCyan(string $text): string
    {
        return $this->rule(Decorate::TEXT_CYAN, $text);
    }

    /**
     * Apply light gray text color.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textLightGray(string $text): string
    {
        return $this->rule(Decorate::TEXT_LIGHT_GRAY, $text);
    }

    /**
     * Apply dark gray text color.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textDarkGray(string $text): string
    {
        return $this->rule(Decorate::TEXT_DARK_GRAY, $text);
    }

    /**
     * Apply light red text color.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textLightRed(string $text): string
    {
        return $this->rule(Decorate::TEXT_LIGHT_RED, $text);
    }

    /**
     * Apply light green text color.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textLightGreen(string $text): string
    {
        return $this->rule(Decorate::TEXT_LIGHT_GREEN, $text);
    }

    /**
     * Apply light yellow text color.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textLightYellow(string $text): string
    {
        return $this->rule(Decorate::TEXT_LIGHT_YELLOW, $text);
    }

    /**
     * Apply light blue text color.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textLightBlue(string $text): string
    {
        return $this->rule(Decorate::TEXT_LIGHT_BLUE, $text);
    }

    /**
     * Apply light magenta text color.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textLightMagenta(string $text): string
    {
        return $this->rule(Decorate::TEXT_LIGHT_MAGENTA, $text);
    }

    /**
     * Apply light cyan text color.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textLightCyan(string $text): string
    {
        return $this->rule(Decorate::TEXT_LIGHT_CYAN, $text);
    }

    /**
     * Apply white text color.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textWhite(string $text): string
    {
        return $this->rule(Decorate::TEXT_WHITE, $text);
    }

    /**
     * Apply red background.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgRed(string $text): string
    {
        return $this->rule(Decorate::BG_RED, $text);
    }

    /**
     * Apply yellow background.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgYellow(string $text): string
    {
        return $this->rule(Decorate::BG_YELLOW, $text);
    }

    /**
     * Apply green background.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgGreen(string $text): string
    {
        return $this->rule(Decorate::BG_GREEN, $text);
    }

    /**
     * Apply blue background.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgBlue(string $text): string
    {
        return $this->rule(Decorate::BG_BLUE, $text);
    }

    /**
     * Apply magenta background.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgMagenta(string $text): string
    {
        return $this->rule(Decorate::BG_MAGENTA, $text);
    }

    /**
     * Apply cyan background.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgCyan(string $text): string
    {
        return $this->rule(Decorate::BG_CYAN, $text);
    }

    /**
     * Apply light gray background.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgLightGray(string $text): string
    {
        return $this->rule(Decorate::BG_LIGHT_GRAY, $text);
    }

    /**
     * Apply dark gray background.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgDarkGray(string $text): string
    {
        return $this->rule(Decorate::BG_DARK_GRAY, $text);
    }

    /**
     * Apply light red background.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgLightRed(string $text): string
    {
        return $this->rule(Decorate::BG_LIGHT_RED, $text);
    }

    /**
     * Apply light green background.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgLightGreen(string $text): string
    {
        return $this->rule(Decorate::BG_LIGHT_GREEN, $text);
    }

    /**
     * Apply light yellow background.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgLightYellow(string $text): string
    {
        return $this->rule(Decorate::BG_LIGHT_YELLOW, $text);
    }

    /**
     * Apply light blue background.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgLightBlue(string $text): string
    {
        return $this->rule(Decorate::BG_LIGHT_BLUE, $text);
    }

    /**
     * Apply light magenta background.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgLightMagenta(string $text): string
    {
        return $this->rule(Decorate::BG_LIGHT_MAGENTA, $text);
    }

    /**
     * Apply light cyan background.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgLightCyan(string $text): string
    {
        return $this->rule(Decorate::BG_LIGHT_CYAN, $text);
    }

    /**
     * Apply white background.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgWhite(string $text): string
    {
        return $this->rule(Decorate::BG_WHITE, $text);
    }

    /**
     * Just-In-Time foreground color using ForegroundColor object.
     *
     * @param ForegroundColor $color Foreground color object
     * @param string|null $text Text to colorize
     * @return string Colored text
     */
    protected function textColor(ForegroundColor $color, ?string $text = null): string
    {
        return $this->rules($color->getRule(), $text);
    }

    /**
     * Just-In-Time background color using BackgroundColor object.
     *
     * @param BackgroundColor $color Background color object
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function bgColor(BackgroundColor $color, string $text): string
    {
        return $this->rules($color->getRule(), $text);
    }

    /**
     * Apply bold text.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textBold(string $text): string
    {
        return $this->rule(Decorate::BOLD, $text, true, Decorate::RESET_BOLD);
    }

    /**
     * Apply underlined text.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textUnderline(string $text): string
    {
        return $this->rule(Decorate::UNDERLINE, $text, true, Decorate::RESET_UNDERLINE);
    }

    /**
     * Apply blinking text.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textBlink(string $text): string
    {
        return $this->rule(Decorate::BLINK, $text, true, Decorate::RESET_BLINK);
    }

    /**
     * Apply reversed text colors.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textReverse(string $text): string
    {
        return $this->rule(Decorate::REVERSE, $text, true, Decorate::RESET_REVERSE);
    }

    /**
     * Apply hidden text.
     *
     * @param string $text Text to colorize
     * @return string Colored text
     */
    protected function textHidden(string $text): string
    {
        return $this->rule(Decorate::HIDDEN, $text, true, Decorate::RESET_HIDDEN);
    }
}
