<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Omega\View\Templator;
use Omega\View\Templator\JsonTemplator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(JsonTemplator::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
final class JsonTest extends TestCase
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

    public function testCanRenderJson(): void
    {
        $out = $this->templator->templates('<html><head></head><body>{% json($data) %}</body></html>');

        $this->assertEquals(
            '<html><head></head><body><?php echo json_encode('
            . '$data, 0 | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR, 512'
            . '); ?></body></html>',
            $out
        );
    }

    public function testCanRenderJsonWithOptionalParam(): void
    {
        $out = $this->templator->templates('<html><head></head><body>{% json($data, 1, 500) %}</body></html>');

        $this->assertEquals(
            '<html><head></head><body><?php echo json_encode('
            . '$data, 1 | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR, 500'
            . '); ?></body></html>',
            $out
        );
    }
}
