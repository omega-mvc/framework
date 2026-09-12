<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Exception;
use Omega\View\Templator;
use Omega\View\Templator\BooleanTemplator;
use Omega\View\TemplatorFinder;
use Tests\FixturesPathTrait;

uses(FixturesPathTrait::class);

covers(BooleanTemplator::class);
covers(Templator::class);
covers(TemplatorFinder::class);

beforeEach(function (): void {
    $this->templator = new Templator(
        new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/')], ['']),
        $this->setFixturePath('/fixtures/view/templator/')
    );
});

it('can render boolean', function (): void {
    $out = $this->templator->templates('<input x-enable="{% bool(1 == 1) %}">');
    expect($out)->toEqual(
        '<input x-enable="<?= (1 == 1) ? \'true\' : \'false\' ?>">'
    );
});
