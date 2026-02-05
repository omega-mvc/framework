<?php

/**
 * Part of Omega - Tests\View Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\View\Templator;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use Tests\View\AbstractViewPath;

/**
 * Test suite for the BreakTemplator within foreach loops.
 *
 * Validates that the `{% break %}` directive is correctly rendered
 * into PHP code in various contexts.
 *
 * @category   Tests
 * @package    View
 * @subpackage Templator
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
final class EachBreakTest extends AbstractViewPath
{
    /**
     * Test it can render each break.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderEachBreak(): void
    {
        $out = $this->getTemplator()->templates(
            '<html><head></head><body>{% foreach ($numbers as $number) %}{% break %}{% endforeach %}</body></html>'
        );
        $this->assertEquals(
            '<html><head></head><body>'
            . '<?php foreach ($numbers as $number): ?>'
            . '<?php break ; ?>'
            . '<?php endforeach; ?>'
            . '</body></html>',
            $out
        );
    }
}
