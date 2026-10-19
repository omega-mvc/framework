<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Omega\View\Templator;
use Omega\View\Templator\ContinueTemplator;
use Omega\View\Templator\EachTemplator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(ContinueTemplator::class)]
#[CoversClass(EachTemplator::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
final class EachTest extends TestCase
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

    public function testCanRenderEach(): void
    {
        $out = $this->templator->templates('{% foreach ($numbers as $number) %}{{ $number }}{% endforeach %}');

        $this->assertEquals(
            '<?php foreach ($numbers as $number): ?><?php echo htmlspecialchars($number); ?><?php endforeach; ?>',
            $out
        );
    }

    public function testCanRenderEachWithoutCurveBraces(): void
    {
        $out = $this->templator->templates('{% foreach $numbers as $number %}{{ $number }}{% endforeach %}');

        $this->assertEquals(
            '<?php foreach ($numbers as $number): ?><?php echo htmlspecialchars($number); ?><?php endforeach; ?>',
            $out
        );
    }

    public function testCanRenderEachWithKeyValue(): void
    {
        $out = $this->templator->templates('{% foreach ($numbers as $key => $number) %}{{ $number }}{% endforeach %}');

        $this->assertEquals(
            '<?php foreach ($numbers as $key => $number): ?>'
            . '<?php echo htmlspecialchars($number); ?>'
            . '<?php endforeach; ?>',
            $out
        );
    }

    public function testCanRenderNestedEach(): void
    {
        $template = '{% foreach ($categories as $category) %}{{ $category->name }}'
            . '{% foreach ($category->items as $item) %}{{ $item->name }}{% endforeach %}{% endforeach %}';

        $expected = '<?php foreach ($categories as $category): ?>'
            . '<?php echo htmlspecialchars($category->name); ?>'
            . '<?php foreach ($category->items as $item): ?>'
            . '<?php echo htmlspecialchars($item->name); ?>'
            . '<?php endforeach; ?>'
            . '<?php endforeach; ?>';

        $out = $this->templator->templates($template);

        $this->assertEquals($expected, $out);
    }

    public function testCanRenderNestedEachWithKeyValue(): void
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

        $out = $this->templator->templates($template);

        $this->assertEquals($expected, $out);
    }

    public function testCanRenderMultipleForeachBlocks(): void
    {
        $template = '{% foreach ($users as $user) %}{{ $user->name }}{% endforeach %}'
            . '{% foreach ($products as $product) %}{{ $product->name }}{% endforeach %}';
        $expected = '<?php foreach ($users as $user): ?>'
            . '<?php echo htmlspecialchars($user->name); ?>'
            . '<?php endforeach; ?>'
            . '<?php foreach ($products as $product): ?>'
            . '<?php echo htmlspecialchars($product->name); ?>'
            . '<?php endforeach; ?>';

        $out = $this->templator->templates($template);

        $this->assertEquals($expected, $out);
    }
}
