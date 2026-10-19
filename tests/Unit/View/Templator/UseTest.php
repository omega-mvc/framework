<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Omega\Text\Str;
use Omega\View\Templator;
use Omega\View\Templator\UseTemplator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(UseTemplator::class)]
#[CoversClass(Str::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
final class UseTest extends TestCase
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

    public function testCanRenderUse(): void
    {
        $out = $this->templator->templates("'<html>{% use ('Test\Test') %}</html>");

        $this->assertTrue(Str::contains($out, 'use Test\Test'));
    }

    public function testCanRenderUseMultiTime(): void
    {
        $out = $this->templator->templates(
            "'<html>{% use ('Test\Test') %}{% use ('Test\Test as Test2') %}</html>"
        );

        $this->assertTrue(Str::contains($out, 'use Test\Test'));
        $this->assertTrue(Str::contains($out, 'use Test\Test as Test2'));
    }

    public function testReturnsTemplateIfNoUseDirective(): void
    {
        $templator = new UseTemplator(
            new TemplatorFinder([__DIR__ . '/../fixtures/view/templator/view/'], ['']),
            __DIR__ . '/../fixtures/view/templator/'
        );

        $template = "<html><body>No use here</body></html>";

        $out = $templator->parse($template);

        $this->assertEquals($template, $out);
    }
}
