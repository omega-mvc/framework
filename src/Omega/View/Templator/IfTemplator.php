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
        // Regex unificata: cattura 'if', 'else' o 'endif' in un colpo solo
        // Usiamo i named groups (?<name>) per rendere il codice leggibile
        $pattern = '/{%\s*(?<type>if|else|endif)(?:\s+(?<condition>[^%]+))?\s*%}/';

        return preg_replace_callback($pattern, function ($matches) {
            $type = $matches['type'];

            return match ($type) {
                'if'    => sprintf('<?php if (%s): ?>', trim($matches['condition'])),
                'else'  => '<?php else: ?>',
                'endif' => '<?php endif; ?>',
                default => $matches[0], // Fallback di sicurezza
            };
        }, $template);
    }
}
