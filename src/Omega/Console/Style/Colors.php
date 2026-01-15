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

namespace Omega\Console\Style;

use Omega\Console\Exceptions\InvalidHexCodeException;
use Omega\Console\Style\Color\BackgroundColor;
use Omega\Console\Style\Color\ForegroundColor;
use Omega\Text\Str;

use function sscanf;

/**
 * Class Colors
 *
 * Provides utilities to convert hexadecimal or RGB colors to terminal color codes.
 * Supports both foreground (text) and background colors.
 *
 * Example usage:
 * ```php
 * echo (string) Colors::hexText('#ff0000'); // red text
 * echo (string) Colors::rgbBg(0, 255, 0);  // green background
 * ```
 * @category   Omega
 * @package    Console
 * @subpackage Style
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class Colors
{
    /**
     * Convert a hex color code to a terminal foreground (text) color.
     *
     * @param string $hexCode Hexadecimal color code starting with '#'
     * @return ForegroundColor Terminal foreground color representation
     * @throws InvalidHexCodeException if the hex code is invalid
     */
    public static function hexText(string $hexCode): ForegroundColor
    {
        if (!Str::is($hexCode, '/^#[0-9a-fA-F]{6}$/i')) {
            throw new InvalidHexCodeException('Hex code not found.');
        }

        [$r, $g, $b] = sscanf($hexCode, '#%02x%02x%02x');

        return self::rgbText($r, $g, $b);
    }

    /**
     * Convert a hex color code to a terminal background color.
     *
     * @param string $hexCode Hexadecimal color code starting with '#'
     * @return BackgroundColor Terminal background color representation
     * @throws InvalidHexCodeException if the hex code is invalid
     */
    public static function hexBg(string $hexCode): BackgroundColor
    {
        if (!Str::is($hexCode, '/^#[0-9a-fA-F]{6}$/i')) {
            throw new InvalidHexCodeException('Hex code not found.');
        }

        [$r, $g, $b] = sscanf($hexCode, '#%02x%02x%02x');

        return self::rgbBg($r, $g, $b);
    }

    /**
     * Convert RGB color to a terminal foreground (text) color.
     *
     * @param int $r Red channel (0–255)
     * @param int $g Green channel (0–255)
     * @param int $b Blue channel (0–255)
     * @return ForegroundColor Terminal foreground color
     */
    public static function rgbText(int $r, int $g, int $b): ForegroundColor
    {
        return new ForegroundColor(self::buildRgbArray(38, $r, $g, $b));
    }

    /**
     * Convert RGB color to a terminal background color.
     *
     * @param int $r Red channel (0–255)
     * @param int $g Green channel (0–255)
     * @param int $b Blue channel (0–255)
     * @return BackgroundColor Terminal background color
     */
    public static function rgbBg(int $r, int $g, int $b): BackgroundColor
    {
        return new BackgroundColor(self::buildRgbArray(48, $r, $g, $b));
    }

    /**
     * Build a normalized RGB array suitable for terminal color codes.
     *
     * @param int $prefix Terminal color prefix (38 for foreground, 48 for background)
     * @param int $r Red channel (0–255)
     * @param int $g Green channel (0–255)
     * @param int $b Blue channel (0–255)
     * @return int[] Array representing terminal color [prefix, mode, r, g, b]
     * @throws InvalidHexCodeException if prefix is not 38 or 48
     */
    private static function buildRgbArray(int $prefix, int $r, int $g, int $b): array
    {
        if ($prefix !== 38 && $prefix !== 48) {
            throw new InvalidHexCodeException('Invalid terminal color prefix. Must be 38 or 48.');
        }

        $r = max(0, min($r, 255));
        $g = max(0, min($g, 255));
        $b = max(0, min($b, 255));

        return [$prefix, 2, $r, $g, $b];
    }
}
