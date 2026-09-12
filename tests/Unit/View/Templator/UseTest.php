<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Exception;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use Omega\View\Templator\UseTemplator;
use Omega\Text\Str;
use Tests\FixturesPathTrait;

uses(FixturesPathTrait::class);

covers(UseTemplator::class);
covers(Str::class);
covers(Templator::class);
covers(TemplatorFinder::class);

beforeEach(function (): void {
    $this->templator = new Templator(
        new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/view/')], ['']),
        $this->setFixturePath('/fixtures/view/templator/')
    );
});

it('can render use', function (): void {
    $out   = $this->templator->templates("'<html>{% use ('Test\Test') %}</html>");
    expect(Str::contains($out, 'use Test\Test'))->toBeTrue();
});

it('can render use multi time', function (): void {
    $out   = $this->templator->templates(
        "'<html>{% use ('Test\Test') %}{% use ('Test\Test as Test2') %}</html>"
    );
    expect(Str::contains($out, 'use Test\Test'))->toBeTrue();
    expect(Str::contains($out, 'use Test\Test as Test2'))->toBeTrue();
});

it('returns template if no use directive', function (): void {
    $templator = new UseTemplator(
        new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/view/')], ['']),
        $this->setFixturePath('/fixtures/view/templator/')
    );

    $template = "<html><body>No use here</body></html>";

    $out = $templator->parse($template);

    expect($out)->toEqual($template);
});
