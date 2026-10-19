<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Omega\View\Templator;
use Omega\View\Templator\NameTemplator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(NameTemplator::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
final class NamingTest extends TestCase
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

    public function testCanRenderNaming(): void
    {
        $out = $this->templator->templates(
            '<html><head></head><body><h1>your {{ $name }}, ages {{ $age }} </h1></body></html>'
        );

        $this->assertEquals(
            '<html><head></head><body><h1>your <?php echo htmlspecialchars($name); ?>, '
            . 'ages <?php echo htmlspecialchars($age); ?> </h1></body></html>',
            $out
        );
    }

    public function testCanRenderNamingWithoutEscape(): void
    {
        $out = $this->templator->templates(
            '<html><head></head><body><h1>your {!! $name !!}, '
            . 'ages {!! $age !!} </h1></body></html>'
        );

        $this->assertEquals(
            '<html><head></head><body><h1>your <?php echo $name; ?>, '
            . 'ages <?php echo $age; ?> </h1></body></html>',
            $out
        );
    }

    public function testCanRenderNamingWithCallFunction(): void
    {
        $out = $this->templator->templates(
            '<html><head></head><body><h1>time: }{{ now()->getTimestamp() }}</h1></body></html>'
        );

        $this->assertEquals(
            '<html><head></head><body><h1>time: }<?php echo htmlspecialchars'
            . '(now()->getTimestamp()); ?></h1></body></html>',
            $out
        );
    }

    public function testCanRenderNamingTernary(): void
    {
        $out = $this->templator->templates(
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

    public function testCanRenderNamingSkip(): void
    {
        $out = $this->templator->templates(
            '<html><head></head><body><h1>{{ $render }}, '
            . '{% raw %}your {{ name }}, ages {{ age }}{% endraw %}</h1></body></html>'
        );

        $this->assertEquals(
            '<html><head></head><body><h1><?php echo htmlspecialchars($render); ?>, '
            . 'your {{ name }}, ages {{ age }}</h1></body></html>',
            $out
        );
    }

    public function testHandlesEmptyTemplateWithoutAnyVariables(): void
    {
        $out = $this->templator->templates('');

        $this->assertEquals('', $out);
    }

    public function testHandlesTemplateWithOnlyRawBlocks(): void
    {
        $template = '{% raw %}RAW_CONTENT{% endraw %}';

        $out = $this->templator->templates($template);

        $this->assertEquals('RAW_CONTENT', $out);
    }

    public function testHandlesMultipleRawAndVariables(): void
    {
        $template = '{% raw %}BLOCK1{% endraw %} {{ $var }} {% raw %}BLOCK2{% endraw %}';

        $out = $this->templator->templates($template);

        $expected = 'BLOCK1 <?php echo htmlspecialchars($var); ?> BLOCK2';

        $this->assertEquals($expected, $out);
    }

    public function testRemovesEmptyNameExpressions(): void
    {
        $out = $this->templator->templates('Hello {{ }} World');

        $this->assertEquals('Hello  World', $out);
    }
}
