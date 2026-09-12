<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Exception;
use Omega\View\Templator;
use Omega\View\Templator\ContinueTemplator;
use Omega\View\Templator\EachTemplator;
use Omega\View\TemplatorFinder;
use Tests\FixturesPathTrait;

uses(FixturesPathTrait::class);

covers(ContinueTemplator::class);
covers(EachTemplator::class);
covers(Templator::class);
covers(TemplatorFinder::class);

beforeEach(function (): void {
    $this->templator = new Templator(
        new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/view/')], ['']),
        $this->setFixturePath('/fixtures/view/templator/')
    );
});

it('can render each continue', function (): void {
    $out = $this->templator->templates('{% foreach ($numbers as $number) %}{% continue %}{% endforeach %}');
    expect($out)->toEqual('<?php foreach ($numbers as $number): ?><?php continue ; ?><?php endforeach; ?>');
});
