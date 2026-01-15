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
use PHPUnit\Framework\TestCase;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;

/**
 * Test suite for the IfTemplator.
 *
 * Ensures that `{% if %}`, `{% else %}`, and nested conditions are parsed correctly
 * into valid PHP conditional statements.
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
final class IfTest extends TestCase
{
    /**
     * Test it can render if.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderIf(): void
    {
        $templator = new Templator(new TemplatorFinder([__DIR__]), __DIR__);
        $out = $templator->templates(
            '<html><head></head><body><h1>{% if ($true === true) %} show {% endif %}</h1>'
            . '<h1>{% if ($true === false) %} show {% endif %}</h1></body></html>'
        );
        $this->assertEquals(
            '<html><head></head><body><h1><?php if (($true === true) ): ?> show <?php endif; ?></h1>'
            . '<h1><?php if (($true === false) ): ?> show <?php endif; ?></h1>'
            . '</body></html>',
            $out
        );
    }

    /**
     * Test it can render if else.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderIfElse(): void
    {
        $templator = new Templator(new TemplatorFinder([__DIR__]), __DIR__);
        $out       = $templator->templates('<div>{% if ($condition) %}true case{% else %}false case{% endif %}</div>');
        $this->assertEquals(
            '<div><?php if (($condition) ): ?>true case<?php else: ?>false case<?php endif; ?></div>',
            $out
        );
    }

    /**
     * Test it can render nested if.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderNestedIf(): void
    {
        $templator = new Templator(new TemplatorFinder([__DIR__]), __DIR__);
        $template  = '<div>{% if ($level1) %}Level 1 true{% if ($level2) %}Level 2 true{% endif %}{% endif %}</div>';
        $expected = '<div><?php if (($level1) ): ?>Level 1 true'
            . '<?php if (($level2) ): ?>Level 2 true<?php endif; ?>'
            . '<?php endif; ?></div>';

        $this->assertEquals($expected, $templator->templates($template));
    }

    /**
     * Test it can render complex nested if else.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderComplexNestedIfElse(): void
    {
        $templator = new Templator(new TemplatorFinder([__DIR__]), __DIR__);
        $template = '<div>{% if ($level1) %}Level 1 true'
            . '{% if ($level2) %}Level 2 true{% else %}Level 2 false'
            . '{% if ($level3) %}Level 3 true inside level 2 false{% endif %}{% endif %}'
            . '{% else %}Level 1 false{% if ($otherCondition) %}Other condition true{% endif %}{% endif %}</div>';
        $expected = '<div><?php if (($level1) ): ?>Level 1 true<?php if (($level2) ): ?>Level 2 true<?php else: ?>'
            . 'Level 2 false<?php if (($level3) ): ?>Level 3 true inside level 2 false<?php endif; ?><?php endif; ?>'
            . '<?php else: ?>Level 1 false<?php if (($otherCondition) ): ?>Other condition true<?php endif; ?>'
            . '<?php endif; ?></div>';

        $this->assertEquals($expected, $templator->templates($template));
    }

    /**
     * Test it can handle multiple if blocks with nesting.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanHandleMultipleIfBlocksWithNesting(): void
    {
        $templator = new Templator(new TemplatorFinder([__DIR__]), __DIR__);
        $template = '<div>{% if ($block1) %}Block 1 content{% if ($nested1) %}Nested 1{% endif %}{% endif %}'
            . '{% if ($block2) %}Block 2 content{% if ($nested2) %}Nested 2'
            . '{% if ($deepnested) %}Deep nested{% endif %}{% endif %}'
            . '{% endif %}</div>';
        $expected = '<div><?php if (($block1) ): ?>Block 1 content<?php if (($nested1) ): ?>Nested 1<?php endif; ?>'
            . '<?php endif; ?><?php if (($block2) ): ?>Block 2 content<?php if (($nested2) ): ?>Nested 2'
            . '<?php if (($deepnested) ): ?>Deep nested<?php endif; ?><?php endif; ?>'
            . '<?php endif; ?>'
            . '</div>';

        $this->assertEquals($expected, $templator->templates($template));
    }
}
