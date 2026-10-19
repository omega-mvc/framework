<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Omega\View\Exceptions\ViewFileNotFoundException;
use Omega\View\Templator;
use Omega\View\Templator\IncludeTemplator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use Tests\TestCase;

#[CoversClass(IncludeTemplator::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
#[CoversClass(ViewFileNotFoundException::class)]
final class IncludeTest extends TestCase
{
    private Templator $templator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templator = new Templator(
            new TemplatorFinder([__DIR__ . '/../fixtures/view/templator/'], ['']),
            __DIR__ . '/../fixtures/view/templator/'
        );
    }

    public function testCanRenderInclude(): void
    {
        $out = $this->templator->templates(
            '<html><head></head><body>{% include(\'/view/component.php\') %}</body></html>'
        );

        $this->assertEquals('<html><head></head><body><p>Call From Component</p></body></html>', $out);
    }

    public function testCanFetchDependencyView(): void
    {
        $finder    = new TemplatorFinder([__DIR__ . '/../fixtures/view/templator'], ['']);
        $templator = new Templator($finder, __DIR__ . '/../fixtures/view/templator');

        $templator->templates('<html><head></head><body>{% include(\'view/component.php\') %}</body></html>', 'test');

        $this->assertEquals([
            $finder->find('view/component.php') => 1,
        ], $templator->getDependency('test'));
    }

    public function testThrowsExceptionWhenIncludeNotFound(): void
    {
        $this->expectException(ViewFileNotFoundException::class);
        $this->expectExceptionMessageIsOrContains('View file not found: `nonexistent.php`');

        $this->templator->templates(
            '<html>{% include(\'nonexistent.php\') %}</html>'
        );
    }

    public function testReturnsIncludedTemplateWhenDepthZero(): void
    {
        $reflection = new ReflectionClass($this->templator);
        $property   = $reflection->getProperty('finder');
        /** @var TemplatorFinder $finder */
        $finder = $property->getValue($this->templator);

        $includeTemplator = new IncludeTemplator($finder, __DIR__ . '/../fixtures/view/templator/');
        $includeTemplator->maksDept(0);

        $template = "{% include('view/component.php') %}";
        $out      = $includeTemplator->parse($template);

        $this->assertStringContainsString('<p>Call From Component</p>', $out);
    }
}
