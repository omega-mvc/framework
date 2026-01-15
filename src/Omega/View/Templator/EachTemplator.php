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

/** @noinspection PhpRegExpRedundantModifierInspection */

declare(strict_types=1);

namespace Omega\View\Templator;

use Omega\View\AbstractTemplatorParse;

use function preg_replace;

/**
 * Parses `{% foreach %}` and `{% endforeach %}` directives into PHP foreach loops.
 *
 * Supports both value-only and key/value iteration syntaxes.
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
class EachTemplator extends AbstractTemplatorParse
{
    /**
     * {@inheritdoc}
     */
    public function parse(string $template): string
    {
        $template = preg_replace(
            '/{%\s*foreach\s*\(?\s*([^)\s]+)\s+as\s+([^)\s]+)\s*=>\s*([^)\s]+)\s*\)?\s*%}/s',
            '<?php foreach ($1 as $2 => $3): ?>',
            $template
        );

        $template = preg_replace(
            '/{%\s*foreach\s*\(?\s*([^)\s]+)\s+as\s+([^)\s]+)\s*\)?\s*%}/s',
            '<?php foreach ($1 as $2): ?>',
            $template
        );

        return preg_replace(
            '/{%\s*endforeach\s*%}/s',
            '<?php endforeach; ?>',
            $template
        );
    }
}
