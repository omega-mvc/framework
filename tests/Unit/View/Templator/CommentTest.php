<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Exception;
use Omega\View\Templator;
use Omega\View\Templator\CommentTemplator;
use Omega\View\TemplatorFinder;
use Tests\FixturesPathTrait;

uses(FixturesPathTrait::class);

covers(CommentTemplator::class);
covers(Templator::class);
covers(TemplatorFinder::class);

beforeEach(function (): void {
    $this->templator = new Templator(
        new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/')], ['']),
        $this->setFixturePath('/fixtures/view/templator/')
    );
});

it('can render each break', function (): void {
    $out = $this->templator->templates('<html><head></head><body>{# this a comment #}</body></html>');
    expect($out)->toEqual('<html><head></head><body><?php /* this a comment */ ?></body></html>');
});
