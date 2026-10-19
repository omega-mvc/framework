<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Omega\View\Exceptions\YeldSectionNotFoundException;
use Omega\View\Templator;
use Omega\View\Templator\ComponentTemplator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;
use Throwable;

use function trim;

#[CoversClass(ComponentTemplator::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
#[CoversClass(YeldSectionNotFoundException::class)]
final class ComponentTest extends TestCase
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

    public function testCanRenderComponentScope(): void
    {
        $out = $this->templator->templates(
            '{% component(\'component.template\') %}<main>core component</main>{% endcomponent %}'
        );

        $this->assertEquals('<html><head></head><body><main>core component</main></body></html>', trim($out));
    }

    public function testCanRenderNestedComponentScope(): void
    {
        $out = $this->templator->templates(
            '{% component(\'componentnested.template\') %}card with nest{% endcomponent %}'
        );

        $this->assertEquals(
            '<html><head></head><body><div class="card">card with nest</div>'
            . PHP_EOL
            . '</body></html>',
            trim($out)
        );
    }

    public function testCanRenderComponentScopeMultiple(): void
    {
        $out = $this->templator->templates(
            '{% component(\'componentcard.template\') %}oke{% endcomponent %} '
            . '{% component(\'componentcard.template\') %}oke 2 {% endcomponent %}'
        );

        $this->assertEquals(
            '<div class="card">oke</div>'
            . PHP_EOL
            . ' <div class="card">oke 2 </div>',
            trim($out)
        );
    }

    public function testThrowWhenExtendNotFound(): void
    {
        try {
            $this->templator->templates(
                '{% component(\'notexits.template\') %}<main>core component</main>{% endcomponent %}'
            );
        } catch (Throwable $th) {
            $this->assertEquals(
                'View file not found: `notexits.template`',
                $th->getMessage()
            );
        }
    }

    public function testThrowWhenExtendNotFoundYield(): void
    {
        try {
            $this->templator->templates(
                '{% component(\'componentyield.template\') %}<main>core component</main>{% endcomponent %}'
            );
        } catch (Throwable $th) {
            $this->assertEquals('Yield section not found: `component2.template`', $th->getMessage());
        }
    }

    public function testCanRenderComponentUsingNamedParameter(): void
    {
        $out = $this->templator->templates(
            '{% component(\'componentnamed.template\', bg:\'bg-red\', size:"md") %}inner text{% endcomponent %}'
        );

        $this->assertEquals('<p class="bg-red md">inner text</p>', trim($out));
    }

    public function testCanRenderComponentOppAProcess(): void
    {
        $templator = $this->templator;
        $templator->setComponentNamespace('Tests\\View\\Templator\\');
        $out = $templator->templates(
            '{% component(\'TestClassComponent\', bg:\'bg-red\', size:"md") %}inner text{% endcomponent %}'
        );

        $this->assertEquals('<p class="bg-red md">inner text</p>', trim($out));
    }

    public function testCanGetDependencyView(): void
    {
        $finder    = new TemplatorFinder([__DIR__ . '/../fixtures/view/templator/view/'], ['']);
        $templator = new Templator($finder, __DIR__ . '/../fixtures/view/templator/');

        $templator->templates(
            '{% component(\'component.template\') %}<main>core component</main>{% endcomponent %}',
            'test'
        );

        $this->assertEquals([
            $finder->find('component.template') => 1,
        ], $templator->getDependency('test'));
    }

    public function testExtractComponentAndParamsWithPositionalParam(): void
    {
        $reflection = new ReflectionClass($this->templator);
        $property   = $reflection->getProperty('finder');
        /** @var TemplatorFinder $finder */
        $finder = $property->getValue($this->templator);

        $componentTemplator = new ComponentTemplator($finder, __DIR__ . '/../fixtures/view/templator/');

        $method = new ReflectionMethod(ComponentTemplator::class, 'extractComponentAndParams');

        /** @var array{string, array<int, mixed>} $result */
        $result = $method->invoke($componentTemplator, "'MyComp', 'simple'");
        [$name, $params] = $result;

        $this->assertEquals('MyComp', $name);
        $this->assertEquals(['simple'], $params);
    }

    public function testFallsBackToTemplateLookupWhenClassExistsButIsNotAComponent(): void
    {
        $templator = new Templator(
            new TemplatorFinder([__DIR__ . '/../fixtures/view/templator/view/'], ['']),
            __DIR__ . '/../fixtures/view/templator/'
        );
        $templator->setComponentNamespace('Omega\\View\\Exceptions\\');

        try {
            $templator->templates(
                '{% component(\'YeldSectionNotFoundException\') %}<main>core component</main>{% endcomponent %}'
            );
        } catch (Throwable $th) {
            $this->assertEquals(
                'View file not found: `YeldSectionNotFoundException`',
                $th->getMessage()
            );
        }
    }
}
