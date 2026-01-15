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

use function preg_replace_callback;

/**
 * Renders JSON output from template expressions using `json_encode`.
 *
 * Supports optional flags and depth parameters and enforces safe JSON encoding.
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
class JsonTemplator extends AbstractTemplatorParse
{
    /**
     * {@inheritdoc}
     */
    public function parse(string $template): string
    {
        return preg_replace_callback(
            '/{%\s*json\(\s*(.+?)\s*(?:,\s*(\d+)\s*)?(?:,\s*(\d+)\s*)?\)\s*%}/',
            static function ($matches): string {
                $data  = $matches[1];
                $flags = $matches[2] ?? 0;
                $depth = $matches[3] ?? 512;

                return "<?php echo json_encode("
                    . $data
                    . ", "
                    . $flags
                    . " | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR, "
                    . $depth
                    . "); ?>";
            },
            $template
        );
    }
}
