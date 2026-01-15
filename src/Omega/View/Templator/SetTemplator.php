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
 * SetTemplator handles variable assignment within templates.
 *
 * It parses `{% set $variable = value %}` directives and transforms them
 * into standard PHP variable assignments, enabling simple state
 * management inside template files.
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
class SetTemplator extends AbstractTemplatorParse
{
    /**
     * {@inheritdoc}
     */
    public function parse(string $template): string
    {
        return preg_replace(
            '/{%\s*set\s+(\$\w+)\s*=\s*(.*?)\s*%}/',
            '<?php $1 = $2; ?>',
            $template
        );
    }
}
