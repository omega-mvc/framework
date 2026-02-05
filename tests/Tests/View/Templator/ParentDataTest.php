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
 * Test suite for parent data rendering in templates.
 *
 * Ensures that parent-level data is correctly accessible inside templates.
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
final class ParentDataTest extends AbstractViewPath
{
    /**
     * Test it render parent data.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderParentData(): void
    {
        $out = $this->getTemplator()->templates(
            '<html><head></head><body><h1>my name is {{ $__[\'full.name\'] }} </h1></body></html>'
        );
        $this->assertEquals(
            '<html><head></head><body><h1>my name is '
            . '<?php echo htmlspecialchars($__[\'full.name\']); ?>'
            . ' </h1></body></html>',
            $out
        );
    }
}
