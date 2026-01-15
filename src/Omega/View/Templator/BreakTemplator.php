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

/**
 * Parses break statements in templates and converts them into PHP break statements.
 *
 * Recognizes the syntax `{% break %}` or `{% break n %}` and outputs a PHP break
 * statement, optionally with a numeric argument.
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
class BreakTemplator extends AbstractTemplatorParse
{
    /**
     * {@inheritdoc}
     */
    public function parse(string $template): string
    {
        return preg_replace(
            '/\{%\s*break\s*(\d*)\s*%\}/',
            '<?php break $1; ?>',
            $template
        );
    }
}
