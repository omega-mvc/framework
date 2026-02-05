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
use Throwable;

use function trim;

use const PHP_EOL;

/**
 * Test suite for section and layout inheritance features of the templator.
 *
 * This class verifies the correct behavior of section-related directives,
 * including template extension, section definition, inline sections,
 * multiple sections, default yields, multi-line sections, and dependency
 * tracking between parent and child templates.
 *
 * It also ensures that proper exceptions are thrown when:
 * - An extended template cannot be found.
 * - Multiple default yields are defined.
 * - Invalid section configurations are used.
 *
 * The tests cover both rendering correctness and internal dependency
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
final class SectionTest extends AbstractViewPath
{
    /**
     * Test it can render section scope.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderSectionScope(): void
    {
        $out = $this->getTemplator('templator/view/')->templates(
            '{% extend(\'section.template\') %} {% section(\'title\') %}<strong>taylor</strong>{% endsection %}'
        );
        $this->assertEquals('<p><strong>taylor</strong></p>', trim($out));
    }

    /**
     * Test it throw when extend not found.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItThrowWhenExtendNotFound(): void
    {
        try {
            $this->getTemplator('templator/view/')->templates(
                '{% extend(\'section.html\') %} {% section(\'title\') %}<strong>taylor</strong>{% endsection %}'
            );
        } catch (Throwable $th) {
            $this->assertEquals('Template file not found: section.html', $th->getMessage());
        }
    }

    /**
     * Test it can render section in line.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderSectionInline(): void
    {
        $out = $this->getTemplator('templator/view/')->templates('{% extend(\'section.template\') %} {% section(\'title\', \'taylor\') %}');
        $this->assertEquals('<p>taylor</p>', trim($out));
    }

    /**
     * Test it can render section in line escape.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderSectionInlineEscape(): void
    {
        $out = $this->getTemplator('templator/view/')->templates(
            '{% extend(\'section.template\') %} {% section(\'title\', \'<script>alert(1)</script>\') %}'
        );
        $this->assertEquals('<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>', trim($out));
    }

    /**
     * Test it can render multisection.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderMultiSection(): void
    {
        $out = $this->getTemplator('templator/view/')->templates('
            {% extend(\'section.template\') %}

            {% sections %}
            title : <strong>taylor</strong>
            {% endsections %}
        ');
        $this->assertEquals('<p><strong>taylor</strong></p>', trim($out));
    }

    /**
     * Test it can get dependency view.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanGetDependencyView(): void
    {
        $finder    = new TemplatorFinder([$this->viewPath('templator/view/')], ['']);
        $templator = new Templator($finder, $this->viewCache('templator'));
        $templator->templates(
            '{% extend(\'section.template\') %} {% section(\'title\') %}<strong>taylor</strong>{% endsection %}',
            'test'
        );
        $this->assertEquals([
            $finder->find('section.template') => 1,
        ], $templator->getDependency('test'));
    }

    /**
     * Test it can render section scope with default yield.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderSectionScopeWithDefaultYield(): void
    {
        $out = $this->getTemplator('templator/view/')->templates('{% extend(\'sectiondefault.template\') %}');
        $this->assertEquals('<p>nuno</p>', trim($out));
    }

    /**
     * Test it can render section with multi line.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderSectionWithMultiLine(): void
    {
        $out = $this->getTemplator('templator/view/')->templates('{% extend(\'sectiondefaultmultilines.template\') %}');
        $this->assertEquals(
            '<li>'
            . PHP_EOL
            . '<ul>one</ul>'
            . PHP_EOL
            . '<ul>two</ul>'
            . PHP_EOL
            . '<ul>three</ul>'
            . PHP_EOL
            . '</li>',
            trim($out)
        );
    }

    /**
     * Test it will throw error have default.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItWillThrowErrorHaveTwoDefault(): void
    {
        $this->expectExceptionMessage('The yield statement cannot have both a default value and content.');
        $this->getTemplator('templator/view/')->templates('{% extend(\'sectiondefaultandmultilines.template\') %}');
    }
}
