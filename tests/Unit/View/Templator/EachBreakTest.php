<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Omega\View\Templator;
use Omega\View\Templator\BreakTemplator;
use Omega\View\Templator\EachTemplator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(BreakTemplator::class)]
#[CoversClass(EachTemplator::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
final class EachBreakTest extends TestCase
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

    public function testCanRenderEachBreak(): void
    {
        $out = $this->templator->templates(
            '<html><head></head><body>{% foreach ($numbers as $number) %}{% break %}{% endforeach %}</body></html>'
        );

        $this->assertEquals(
            '<html><head></head><body>'
            . '<?php foreach ($numbers as $number): ?>'
            . '<?php break ; ?>'
            . '<?php endforeach; ?>'
            . '</body></html>',
            $out
        );
    }
}
