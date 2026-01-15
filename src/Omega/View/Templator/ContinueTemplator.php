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
 * Transforms `{% continue %}` directives into native PHP `continue` statements.
 *
 * Supports optional numeric levels to control how many loop levels to skip.
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
class ContinueTemplator extends AbstractTemplatorParse
{
    /**
     * {@inheritdoc}
     */
    public function parse(string $template): string
    {
        return preg_replace(
            '/\{%\s*continue\s*(\d*)\s*%\}/',
            '<?php continue $1; ?>',
            $template
        );
    }
}
