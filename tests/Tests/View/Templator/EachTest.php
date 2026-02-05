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
 * Test suite for the ContinueTemplator within foreach loops.
 *
 * Validates that the `{% continue %}` directive is correctly rendered
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
final class EachTest extends AbstractViewPath
{
    /**
     * Test it can render each.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderEach(): void
    {
        $out = $this->getTemplator()->templates('{% foreach ($numbers as $number) %}{{ $number }}{% endforeach %}');
        $this->assertEquals(
            '<?php foreach ($numbers as $number): ?><?php echo htmlspecialchars($number); ?><?php endforeach; ?>',
            $out
        );
    }

    /**
     * Test it can render each without curve braces.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderEachWithoutCurveBraces(): void
    {
        $out = $this->getTemplator()->templates('{% foreach $numbers as $number %}{{ $number }}{% endforeach %}');
        $this->assertEquals(
            '<?php foreach ($numbers as $number): ?><?php echo htmlspecialchars($number); ?><?php endforeach; ?>',
            $out
        );
    }

    /**
     * Test it can render each with key values.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderEachWithKeyValue(): void
    {
        $out = $this->getTemplator()->templates('{% foreach ($numbers as $key => $number) %}{{ $number }}{% endforeach %}');
        $this->assertEquals(
            '<?php foreach ($numbers as $key => $number): ?>'
            . '<?php echo htmlspecialchars($number); ?>'
            . '<?php endforeach; ?>',
            $out
        );
    }

    /**
     * Test it can render nested each.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderNestedEach(): void
    {
        $template = '{% foreach ($categories as $category) %}{{ $category->name }}'
            . '{% foreach ($category->items as $item) %}{{ $item->name }}{% endforeach %}{% endforeach %}';

        $expected = '<?php foreach ($categories as $category): ?>'
            . '<?php echo htmlspecialchars($category->name); ?>'
            . '<?php foreach ($category->items as $item): ?>'
            . '<?php echo htmlspecialchars($item->name); ?>'
            . '<?php endforeach; ?>'
            . '<?php endforeach; ?>';

        $out = $this->getTemplator()->templates($template);
        $this->assertEquals($expected, $out);
    }

    /**
     * Test it can render nested each with key value.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderNestedEachWithKeyValue(): void
    {
        $template = '{% foreach ($data as $key => $values) %}{{ $key }}'
            . '{% foreach ($values as $index => $item) %}{{ $index }}: {{ $item }}'
            . '{% endforeach %}{% endforeach %}';
        $expected = '<?php foreach ($data as $key => $values): ?>'
            . '<?php echo htmlspecialchars($key); ?>'
            . '<?php foreach ($values as $index => $item): ?>'
            . '<?php echo htmlspecialchars($index); ?>: '
            . '<?php echo htmlspecialchars($item); ?>'
            . '<?php endforeach; ?>'
            . '<?php endforeach; ?>';

        $out = $this->getTemplator()->templates($template);
        $this->assertEquals($expected, $out);
    }

    /**
     * Test it can render multiple foreach blocks.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderMultipleForeachBlocks(): void
    {
        $template = '{% foreach ($users as $user) %}{{ $user->name }}{% endforeach %}'
            . '{% foreach ($products as $product) %}{{ $product->name }}{% endforeach %}';
        $expected = '<?php foreach ($users as $user): ?>'
            . '<?php echo htmlspecialchars($user->name); ?>'
            . '<?php endforeach; ?>'
            . '<?php foreach ($products as $product): ?>'
            . '<?php echo htmlspecialchars($product->name); ?>'
            . '<?php endforeach; ?>';

        $out = $this->getTemplator()->templates($template);
        $this->assertEquals($expected, $out);
    }
}
