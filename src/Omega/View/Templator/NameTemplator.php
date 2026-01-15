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

use function preg_replace;
use function preg_replace_callback;

/**
 * Handles variable output and raw content rendering within templates.
 *
 * Supports escaped output (`{{ }}`), unescaped output (`{!! !!}`),
 * and raw blocks that bypass parsing.
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
class NameTemplator extends AbstractTemplatorParse
{
    /**
     * {@inheritdoc}
     */
    public function parse(string $template): string
    {
        $rawBlocks = [];
        $template  = preg_replace_callback('/{% raw %}(.*?){% endraw %}/s', function ($matches) use (&$rawBlocks) {
            $rawBlocks[] = $matches[1];

            return '##RAW_BLOCK##';
        }, $template);

        $template = preg_replace('/{!!\s*([^}]+)\s*!!}/', '<?php echo $1; ?>', $template);
        $template = preg_replace_callback(
            '/{{\s*(.*?)\s*}}/s',
            fn($m) => '<?php echo htmlspecialchars(' . trim($m[1]) . '); ?>',
            $template
        );

        foreach ($rawBlocks as $rawBlock) {
            $template = preg_replace('/##RAW_BLOCK##/', $rawBlock, $template, 1);
        }

        return $template;
    }
}
