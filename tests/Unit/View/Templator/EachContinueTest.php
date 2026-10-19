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
final class EachContinueTest extends TestCase
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

    public function testCanRenderEachContinue(): void
    {
        $out = $this->templator->templates('{% foreach ($numbers as $number) %}{% continue %}{% endforeach %}');

        $this->assertEquals(
            '<?php foreach ($numbers as $number): ?><?php continue ; ?><?php endforeach; ?>',
            $out
        );
    }
}
