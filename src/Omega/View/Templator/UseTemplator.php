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

use function array_map;
use function count;
use function implode;
use function preg_match;
use function preg_replace_callback;

/**
 * UseTemplator manages PHP `use` statements declared inside templates.
 *
 * It collects `{% use('Namespace\\Class') %}` directives and injects the
 * corresponding `use` statements at the beginning of the compiled
 * template, ensuring proper namespace imports before execution.
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
class UseTemplator extends AbstractTemplatorParse
{
    /**
     * {@inheritdoc}
     * @noinspection PhpUnnecessaryCurlyVarSyntaxInspection
     */
    public function parse(string $template): string
    {
        /** @noinspection PhpUnusedLocalVariableInspection */
        preg_match('/{%\s*use\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*%}/', $template, $matches);

        $result = preg_replace_callback(
            '/{%\s*use\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*%}/',
            function ($matches) {
                $this->uses[] = $matches[1];

                return '';
            },
            $template
        );

        if (0 === count($this->uses)) {
            return $template;
        }

        $uses      = array_map(fn ($use) => "use {$use};", $this->uses);
        $uses      = implode("\n", $uses);
        $header    = "<?php\n/* begain uses */\n{$uses}\n/* end uses */\n?>\n";

        return $header . $result;
    }
}
