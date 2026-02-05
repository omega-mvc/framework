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
 * Test suite for the IncludeTemplator.
 *
 * Ensures that `{% include %}` directives are processed correctly
 * and that dependencies are tracked when including views.
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
final class IncludeTest extends AbstractViewPath
{
    /**
     * Test it can render include.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderInclude(): void
    {
        $out = $this->getTemplator()->templates(
            '<html><head></head><body>{% include(\'/view/component.php\') %}</body></html>'
        );
        $this->assertEquals('<html><head></head><body><p>Call From Component</p></body></html>', $out);
    }

    /**
     * Test it can fetch dependency view.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanFetchDependencyView(): void
    {
        $finder    = new TemplatorFinder([$this->viewPath('templator')], ['']);
        $templator = new Templator($finder, $this->viewCache('templator'));
        $templator->templates('<html><head></head><body>{% include(\'view/component.php\') %}</body></html>', 'test');
        $this->assertEquals([
            $finder->find('view/component.php') => 1,
        ], $templator->getDependency('test'));
    }
}
