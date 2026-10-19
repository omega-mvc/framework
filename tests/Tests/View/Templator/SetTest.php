<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Omega\View\Templator;
use Omega\View\Templator\SetTemplator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(SetTemplator::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
final class SetTest extends TestCase
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

    public function testCanRenderSetString(): void
    {
        $out = $this->templator->templates('{% set $foo=\'bar\' %}');

        $this->assertEquals('<?php $foo = \'bar\'; ?>', $out);
    }

    public function testCanRenderSetInt(): void
    {
        $out = $this->templator->templates('{% set $bar=123 %}');

        $this->assertEquals('<?php $bar = 123; ?>', $out);
    }

    public function testCanRenderSetArray(): void
    {
        $out = $this->templator->templates('{% set $arr=[12, \'34\'] %}');

        $this->assertEquals('<?php $arr = [12, \'34\']; ?>', $out);
    }
}
