<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
final class ParentDataTest extends TestCase
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

    public function testCanRenderParentData(): void
    {
        $out = $this->templator->templates(
            '<html><head></head><body><h1>my name is {{ $__[\'full.name\'] }} </h1></body></html>'
        );

        $this->assertEquals(
            '<html><head></head><body><h1>my name is '
            . '<?php echo htmlspecialchars($__[\'full.name\']); ?>'
            . ' </h1></body></html>',
            $out
        );
    }
}
