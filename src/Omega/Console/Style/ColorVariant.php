<?php

/** @noinspection PhpUnused */

/**
 * Part of Omega - Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection SpellCheckingInspection Don't touch this suppression. */

declare(strict_types=1);

namespace Omega\Console\Style;

/**
 * Provide color variant.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Style
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class ColorVariant
{
    public const string RED             = '#f44336';
    public const string RED_50          = '#fef2f2';
    public const string RED_100         = '#fee2e2';
    public const string RED_200         = '#fecaca';
    public const string RED_300         = '#fca5a5';
    public const string RED_400         = '#f87171';
    public const string RED_500         = '#ef4444';
    public const string RED_600         = '#dc2626';
    public const string RED_700         = '#b91c1c';
    public const string RED_800         = '#991b1b';
    public const string RED_900         = '#7f1d1d';
    public const string RED_950         = '#450a0a';
    public const string PINK            = '#e91e63';
    public const string PINK_50         = '#fdf2f8';
    public const string PINK_100        = '#fce7f3';
    public const string PINK_200        = '#fbcfe8';
    public const string PINK_300        = '#f9a8d4';
    public const string PINK_400        = '#f472b6';
    public const string PINK_500        = '#ec4899';
    public const string PINK_600        = '#db2777';
    public const string PINK_700        = '#be185d';
    public const string PINK_800        = '#9d174d';
    public const string PINK_900        = '#831843';
    public const string PINK_950        = '#831843';
    public const string PURPLE          = '#9c27b0';
    public const string PURPLE_50       = '#faf5ff';
    public const string PURPLE_100      = '#f3e8ff';
    public const string PURPLE_200      = '#e9d5ff';
    public const string PURPLE_300      = '#d8b4fe';
    public const string PURPLE_400      = '#c084fc';
    public const string PURPLE_500      = '#a855f7';
    public const string PURPLE_600      = '#9333ea';
    public const string PURPLE_700      = '#7e22ce';
    public const string PURPLE_800      = '#6b21a8';
    public const string PURPLE_900      = '#581c87';
    public const string PURPLE_950      = '#4E1877';
    public const string DEEP_PURPLE     = '#673ab7';
    public const string DEEP_PURPLE_50  = '#ede7f6';
    public const string DEEP_PURPLE_100 = '#d1c4e9';
    public const string DEEP_PURPLE_200 = '#b39ddb';
    public const string DEEP_PURPLE_300 = '#9575cd';
    public const string DEEP_PURPLE_400 = '#7e57c2';
    public const string DEEP_PURPLE_500 = '#673ab7';
    public const string DEEP_PURPLE_600 = '#5e35b1';
    public const string DEEP_PURPLE_700 = '#512da8';
    public const string DEEP_PURPLE_800 = '#4527a0';
    public const string DEEP_PURPLE_900 = '#311b92';
    public const string INDIGO          = '#3f51b5';
    public const string INDIGO_50       = '#eef2ff';
    public const string INDIGO_100      = '#e0e7ff';
    public const string INDIGO_200      = '#c7d2fe';
    public const string INDIGO_300      = '#a5b4fc';
    public const string INDIGO_400      = '#818cf8';
    public const string INDIGO_500      = '#6366f1';
    public const string INDIGO_600      = '#4f46e5';
    public const string INDIGO_700      = '#4338ca';
    public const string INDIGO_800      = '#3730a3';
    public const string INDIGO_900      = '#312e81';
    public const string INDIGO_950      = '#1e1b4b';
    public const string BLUE            = '#2196f3';
    public const string BLUE_50         = '#eff6ff';
    public const string BLUE_100        = '#dbeafe';
    public const string BLUE_200        = '#bfdbfe';
    public const string BLUE_300        = '#93c5fd';
    public const string BLUE_400        = '#60a5fa';
    public const string BLUE_500        = '#3b82f6';
    public const string BLUE_600        = '#2563eb';
    public const string BLUE_700        = '#1d4ed8';
    public const string BLUE_800        = '#1e40af';
    public const string BLUE_900        = '#1e3a8a';
    public const string BLUE_950        = '#172554';
    public const string LIGHT_BLUE      = '#03a9f4';
    public const string LIGHT_BLUE_50   = '#f0f9ff';
    public const string LIGHT_BLUE_100  = '#e0f2fe';
    public const string LIGHT_BLUE_200  = '#bae6fd';
    public const string LIGHT_BLUE_300  = '#7dd3fc';
    public const string LIGHT_BLUE_400  = '#38bdf8';
    public const string LIGHT_BLUE_500  = '#0ea5e9';
    public const string LIGHT_BLUE_600  = '#0284c7';
    public const string LIGHT_BLUE_700  = '#0369a1';
    public const string LIGHT_BLUE_800  = '#075985';
    public const string LIGHT_BLUE_900  = '#0c4a6e';
    public const string LIGHT_BLUE_950  = '#082f49';
    public const string CYAN            = '#00bcd4';
    public const string CYAN_50         = '#ecfeff';
    public const string CYAN_100        = '#cffafe';
    public const string CYAN_200        = '#a5f3fc';
    public const string CYAN_300        = '#67e8f9';
    public const string CYAN_400        = '#22d3ee';
    public const string CYAN_500        = '#06b6d4';
    public const string CYAN_600        = '#0891b2';
    public const string CYAN_700        = '#0e7490';
    public const string CYAN_800        = '#155e75';
    public const string CYAN_900        = '#164e63';
    public const string CYAN_950        = '#083344';
    public const string TEAL            = '#009688';
    public const string TEAL_50         = '#f0fdfa';
    public const string TEAL_100        = '#ccfbf1';
    public const string TEAL_200        = '#99f6e4';
    public const string TEAL_300        = '#5eead4';
    public const string TEAL_400        = '#2dd4bf';
    public const string TEAL_500        = '#14b8a6';
    public const string TEAL_600        = '#0d9488';
    public const string TEAL_700        = '#0f766e';
    public const string TEAL_800        = '#115e59';
    public const string TEAL_900        = '#134e4a';
    public const string TEAL_950        = '#042f2e';
    public const string GREEN           = '#4caf50';
    public const string GREEN_50        = '#f0fdf4';
    public const string GREEN_100       = '#dcfce7';
    public const string GREEN_200       = '#bbf7d0';
    public const string GREEN_300       = '#86efac';
    public const string GREEN_400       = '#4ade80';
    public const string GREEN_500       = '#22c55e';
    public const string GREEN_600       = '#16a34a';
    public const string GREEN_700       = '#15803d';
    public const string GREEN_800       = '#166534';
    public const string GREEN_900       = '#14532d';
    public const string GREEN_950       = '#052e16';
    public const string LIGHT_GREEN     = '#8bc34a';
    public const string LIGHT_GREEN_50  = '#f1f8e9';
    public const string LIGHT_GREEN_100 = '#dcedc8';
    public const string LIGHT_GREEN_200 = '#c5e1a5';
    public const string LIGHT_GREEN_300 = '#aed581';
    public const string LIGHT_GREEN_400 = '#9ccc65';
    public const string LIGHT_GREEN_500 = '#8bc34a';
    public const string LIGHT_GREEN_600 = '#7cb342';
    public const string LIGHT_GREEN_700 = '#689f38';
    public const string LIGHT_GREEN_800 = '#558b2f';
    public const string LIGHT_GREEN_900 = '#33691e';
    public const string LIME            = '#cddc39';
    public const string LIME_50         = 'f7fee7';
    public const string LIME_100        = '#ecfccb';
    public const string LIME_200        = '#d9f99d';
    public const string LIME_300        = '#bef264';
    public const string LIME_400        = '#a3e635';
    public const string LIME_500        = '#84cc16';
    public const string LIME_600        = '#65a30d';
    public const string LIME_700        = '#4d7c0f';
    public const string LIME_800        = '#3f6212';
    public const string LIME_900        = '#365314';
    public const string LIME_950        = '#1a2e05';
    public const string YELLOW          = '#ffeb3b';
    public const string YELLOW_50       = '#fefce8';
    public const string YELLOW_100      = '#fef9c3';
    public const string YELLOW_200      = '#fef08a';
    public const string YELLOW_300      = '#fde047';
    public const string YELLOW_400      = '#facc15';
    public const string YELLOW_500      = '#eab308';
    public const string YELLOW_600      = '#ca8a04';
    public const string YELLOW_700      = '#a16207';
    public const string YELLOW_800      = '#854d0e';
    public const string YELLOW_900      = '#713f12';
    public const string YELLOW_950      = '#422006';
    public const string AMBER           = '#fffbeb';
    public const string AMBER_50        = '#fef3c7';
    public const string AMBER_100       = '#fde68a';
    public const string AMBER_200       = '#fcd34d';
    public const string AMBER_300       = '#fbbf24';
    public const string AMBER_400       = '#f59e0b';
    public const string AMBER_500       = '#d97706';
    public const string AMBER_600       = '#b45309';
    public const string AMBER_700       = '#92400e';
    public const string AMBER_800       = '#78350f';
    public const string AMBER_900       = '#451a03';
    public const string ORANGE          = '#ff9800';
    public const string ORANGE_50       = '#fff7ed';
    public const string ORANGE_100      = '#ffedd5';
    public const string ORANGE_200      = '#fed7aa';
    public const string ORANGE_300      = '#fdba74';
    public const string ORANGE_400      = '#fb923c';
    public const string ORANGE_500      = '#f97316';
    public const string ORANGE_600      = '#ea580c';
    public const string ORANGE_700      = '#c2410c';
    public const string ORANGE_800      = '#9a3412';
    public const string ORANGE_900      = '#7c2d12';
    public const string ORANGE_950      = '#431407';
    public const string DEEP_ORANGE     = '#ff5722';
    public const string DEEP_ORANGE_50  = '#fbe9e7';
    public const string DEEP_ORANGE_100 = '#ffccbc';
    public const string DEEP_ORANGE_200 = '#ffab91';
    public const string DEEP_ORANGE_300 = '#ff8a65';
    public const string DEEP_ORANGE_400 = '#ff7043';
    public const string DEEP_ORANGE_500 = '#ff5722';
    public const string DEEP_ORANGE_600 = '#f4511e';
    public const string DEEP_ORANGE_700 = '#e64a19';
    public const string DEEP_ORANGE_800 = '#d84315';
    public const string DEEP_ORANGE_900 = '#bf360c';
    public const string BROWN           = '#795548';
    public const string BROWN_50        = '#efebe9';
    public const string BROWN_100       = '#d7ccc8';
    public const string BROWN_200       = '#bcaaa4';
    public const string BROWN_300       = '#a1887f';
    public const string BROWN_400       = '#8d6e63';
    public const string BROWN_500       = '#795548';
    public const string BROWN_600       = '#6d4c41';
    public const string BROWN_700       = '#5d4037';
    public const string BROWN_800       = '#4e342e';
    public const string BROWN_900       = '#3e2723';
    public const string GREY            = '#9e9e9e';
    public const string GREY_50         = '#f9fafb';
    public const string GREY_100        = '#f3f4f6';
    public const string GREY_200        = '#e5e7eb';
    public const string GREY_300        = '#d1d5db';
    public const string GREY_400        = '#9ca3af';
    public const string GREY_500        = '#6b7280';
    public const string GREY_600        = '#4b5563';
    public const string GREY_700        = '#374151';
    public const string GREY_800        = '#1f2937';
    public const string GREY_900        = '#111827';
    public const string GREY_950        = '#030712';
    public const string BLUE_GREY       = '#607d8b';
    public const string BLUE_GREY_50    = '#f8fafc';
    public const string BLUE_GREY_100   = '#f1f5f9';
    public const string BLUE_GREY_200   = '#e2e8f0';
    public const string BLUE_GREY_300   = '#cbd5e1';
    public const string BLUE_GREY_400   = '#94a3b8';
    public const string BLUE_GREY_500   = '#64748b';
    public const string BLUE_GREY_600   = '#475569';
    public const string BLUE_GREY_700   = '#334155';
    public const string BLUE_GREY_800   = '#1e293b';
    public const string BLUE_GREY_900   = '#0f172a';
    public const string BLUE_GREY_950   = '#020617';

    /**
     * Get constant from string.
     *
     * @param string $name The coloro name.
     * @return string Return the color hex code
     */
    public static function getConstant(string $name): string
    {
        return constant("self::$name");
    }
}
