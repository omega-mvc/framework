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
 * Test suite for the NameTemplator.
 *
 * Ensures that variable interpolation, escapes, raw output,
 * ternary operators, function calls, and raw blocks are rendered correctly.
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
final class NamingTest extends AbstractViewPath
{
    /**
     * Test it can render naming.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderNaming(): void
    {
        $out = $this->getTemplator()->templates(
            '<html><head></head><body><h1>your {{ $name }}, ages {{ $age }} </h1></body></html>'
        );
        $this->assertEquals(
            '<html><head></head><body><h1>your <?php echo htmlspecialchars($name); ?>, '
            . 'ages <?php echo htmlspecialchars($age); ?> </h1></body></html>',
            $out
        );
    }

    /**
     * Test it can render naming without escape.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderNamingWithoutEscape(): void
    {
        $out = $this->getTemplator()->templates(
            '<html><head></head><body><h1>your {!! $name !!}, '
            . 'ages {!! $age !!} </h1></body></html>'
        );
        $this->assertEquals(
            '<html><head></head><body><h1>your <?php echo $name ; ?>, '
            . 'ages <?php echo $age ; ?> </h1></body></html>',
            $out
        );
    }

    /**
     * Test it can render naming with call function.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderNamingWithCallFunction(): void
    {
        $out = $this->getTemplator()->templates(
            '<html><head></head><body><h1>time: }{{ now()->timestamp }}</h1></body></html>'
        );
        $this->assertEquals(
            '<html><head></head><body><h1>time: }<?php echo htmlspecialchars(now()->timestamp); ?></h1></body></html>',
            $out
        );
    }

    /**
     * Test it can render naming ternary.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderNamingTernary(): void
    {
        $out = $this->getTemplator()->templates(
            '<html><head></head><body><h1>your '
            . '{{ $name ?? \'nuno\' }}, ages '
            . '{{ $age ? 17 : 28 }} </h1></body></html>'
        );
        $this->assertEquals(
            '<html><head></head><body><h1>your '
            . '<?php echo htmlspecialchars($name ?? \'nuno\'); ?>, ages '
            . '<?php echo htmlspecialchars($age ? 17 : 28); ?> </h1>'
            . '</body></html>',
            $out
        );
    }

    /**
     * Test it can render naming skip.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderNamingSkip(): void
    {
        $out = $this->getTemplator()->templates(
            '<html><head></head><body><h1>{{ $render }}, '
            . '{% raw %}your {{ name }}, ages {{ age }}{% endraw %}</h1></body></html>'
        );
        $this->assertEquals(
            '<html><head></head><body><h1><?php echo htmlspecialchars($render); ?>, '
            . 'your {{ name }}, ages {{ age }}</h1></body></html>',
            $out
        );
    }
}
