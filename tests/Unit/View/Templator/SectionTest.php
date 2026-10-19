<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Omega\View\Templator;
use Omega\View\Templator\SectionTemplator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;
use Throwable;

use function trim;

use const PHP_EOL;

#[CoversClass(SectionTemplator::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
final class SectionTest extends TestCase
{
    private Templator $templator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templator = new Templator(
            new TemplatorFinder([__DIR__ . '/../fixtures/view/templator/view/'], ['']),
            __DIR__ . '/../fixtures/view/templator/'
        );
    }

    public function testCanRenderSectionScope(): void
    {
        $out = $this->templator->templates(
            '{% extend(\'section.template\') %} {% section(\'title\') %}<strong>taylor</strong>{% endsection %}'
        );

        $this->assertEquals('<p><strong>taylor</strong></p>', trim($out));
    }

    public function testThrowWhenExtendNotFound(): void
    {
        try {
            $this->templator->templates(
                '{% extend(\'section.html\') %} {% section(\'title\') %}<strong>taylor</strong>{% endsection %}'
            );
        } catch (Throwable $th) {
            $this->assertEquals('View file not found: `section.html`', $th->getMessage());
        }
    }

    public function testCanRenderSectionInline(): void
    {
        $out = $this->templator->templates('{% extend(\'section.template\') %} {% section(\'title\', \'taylor\') %}');

        $this->assertEquals('<p>taylor</p>', trim($out));
    }

    public function testCanRenderSectionInlineEscape(): void
    {
        $out = $this->templator->templates(
            '{% extend(\'section.template\') %} {% section(\'title\', \'<script>alert(1)</script>\') %}'
        );

        $this->assertEquals('<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>', trim($out));
    }

    public function testCanRenderMultiSection(): void
    {
        $out = $this->templator->templates('
            {% extend(\'section.template\') %}

            {% sections %}
            title : <strong>taylor</strong>
            {% endsections %}
        ');

        $this->assertEquals('<p><strong>taylor</strong></p>', trim($out));
    }

    public function testCanGetDependencyView(): void
    {
        $finder    = new TemplatorFinder([__DIR__ . '/../fixtures/view/templator/view/'], ['']);
        $templator = new Templator($finder, __DIR__ . '/../fixtures/view/templator');

        $templator->templates(
            '{% extend(\'section.template\') %} {% section(\'title\') %}<strong>taylor</strong>{% endsection %}',
            'test'
        );

        $this->assertEquals([
            $finder->find('section.template') => 1,
        ], $templator->getDependency('test'));
    }

    public function testCanRenderSectionScopeWithDefaultYield(): void
    {
        $out = $this->templator->templates('{% extend(\'sectiondefault.template\') %}');

        $this->assertEquals('<p>nuno</p>', trim($out));
    }

    public function testCanRenderSectionWithMultiLine(): void
    {
        $out = $this->templator->templates('{% extend(\'sectiondefaultmultilines.template\') %}');

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

    public function testWillThrowErrorHaveTwoDefault(): void
    {
        $this->expectExceptionMessageIsOrContains('The yield statement cannot have both a default value and content.');

        $this->templator->templates('{% extend(\'sectiondefaultandmultilines.template\') %}');
    }

    public function testReturnsTemplateIfNoExtend(): void
    {
        $template = '<p>no extend here</p>';
        $out      = $this->templator->templates($template);

        $this->assertEquals('<p>no extend here</p>', $out);
    }

    public function testThrowsWhenRequiredYieldSectionMissing(): void
    {
        $this->expectExceptionMessageIsOrContains(
            "Slot with extends 'sectionwithmissingyield.template' required 'missing_section'"
        );

        $childTemplate = '{% extend(\'sectionwithmissingyield.template\') %}';
        $this->templator->templates($childTemplate);
    }

    public function testReturnsEmptyStringWhenYieldNotDefined(): void
    {
        $layoutPath = __DIR__ . '/../fixtures/view/templator/view/sectionempty.template';

        file_put_contents($layoutPath, '{% yield %}');

        $out = $this->templator->templates('{% extend("sectionempty.template") %}');

        $this->assertEquals('', trim($out));
    }
}
