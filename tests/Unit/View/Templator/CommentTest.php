<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Omega\View\Templator;
use Omega\View\Templator\CommentTemplator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(CommentTemplator::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
final class CommentTest extends TestCase
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

    public function testCanRenderEachBreak(): void
    {
        $out = $this->templator->templates('<html><head></head><body>{# this a comment #}</body></html>');

        $this->assertEquals('<html><head></head><body><?php /* this a comment */ ?></body></html>', $out);
    }
}
