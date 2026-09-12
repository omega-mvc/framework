<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Exception;
use Omega\View\Templator;
use Omega\View\Templator\SetTemplator;
use Omega\View\TemplatorFinder;
use Tests\FixturesPathTrait;

uses(FixturesPathTrait::class);

covers(SetTemplator::class);
covers(Templator::class);
covers(TemplatorFinder::class);

beforeEach(function (): void {
    $this->templator = new Templator(
        new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/')], ['']),
        $this->setFixturePath('/fixtures/view/templator/')
    );
});

it('can render set string', function (): void {
    $out = $this->templator->templates('{% set $foo=\'bar\' %}');
    expect($out)->toEqual('<?php $foo = \'bar\'; ?>');
});

it('can render set int', function (): void {
    $out = $this->templator->templates('{% set $bar=123 %}');
    expect($out)->toEqual('<?php $bar = 123; ?>');
});

it('can render set array', function (): void {
    $out = $this->templator->templates('{% set $arr=[12, \'34\'] %}');
    expect($out)->toEqual('<?php $arr = [12, \'34\']; ?>');
});
