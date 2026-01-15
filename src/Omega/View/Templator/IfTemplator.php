<?php

/**
 * Part of Omega - View Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\View\Templator;

use Omega\View\AbstractTemplatorParse;

use function array_keys;
use function array_reverse;
use function preg_match;
use function preg_match_all;
use function str_replace;
use function substr_replace;

use const PREG_OFFSET_CAPTURE;

/**
 * Converts conditional template directives into PHP `if`, `else`, and `endif` blocks.
 *
 * Preserves the correct order of nested conditions by tracking token positions.
 *
 * @category   Omega
 * @package    View
 * @subpackage Templator
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class IfTemplator extends AbstractTemplatorParse
{
    /**
     * {@inheritdoc}
     */
    public function parse(string $template): string
    {
        $tokens = [
            'if_open' => '/{%\s*if\s+([^%]+)\s*%}/',
            'else'    => '/{%\s*else\s*%}/',
            'endif'   => '/{%\s*endif\s*%}/',
        ];

        $replacements = [
            'if_open' => '<?php if ($1): ?>',
            'else'    => '<?php else: ?>',
            'endif'   => '<?php endif; ?>',
        ];

        $positions = [];

        foreach ($tokens as $type => $pattern) {
            preg_match_all($pattern, $template, $matches, PREG_OFFSET_CAPTURE);
            foreach ($matches[0] as $match) {
                $pos                = $match[0];
                $offset             = $match[1];
                $positions[$offset] = [
                    'type'   => $type,
                    'match'  => $pos,
                    'length' => strlen($pos),
                ];

                if ($type === 'if_open') {
                    preg_match($tokens['if_open'], $pos, $condition);
                    $positions[$offset]['condition'] = $condition[1];
                }
            }
        }

        ksort($positions);

        $result  = $template;
        $offsets = array_reverse(array_keys($positions));

        foreach ($offsets as $offset) {
            $item        = $positions[$offset];
            $type        = $item['type'];
            $replacement = $replacements[$type];

            if ($type === 'if_open') {
                $replacement = str_replace('$1', $item['condition'], $replacement);
            }

            $result = substr_replace(
                $result,
                $replacement,
                $offset,
                $item['length']
            );
        }

        return $result;
    }
}
