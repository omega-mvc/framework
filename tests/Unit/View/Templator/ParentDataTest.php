<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Exception;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use Tests\FixturesPathTrait;

uses(FixturesPathTrait::class);

covers(Templator::class);
covers(TemplatorFinder::class);

beforeEach(function (): void {
    $this->templator = new Templator(
        new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/')], ['']),
        $this->setFixturePath('/fixtures/view/templator/')
    );
});

it('can render parent data', function (): void {
    $out = $this->templator->templates(
        '<html><head></head><body><h1>my name is {{ $__[\'full.name\'] }} </h1></body></html>'
    );
    expect($out)->toEqual(
        '<html><head></head><body><h1>my name is '
        . '<?php echo htmlspecialchars($__[\'full.name\']); ?>'
        . ' </h1></body></html>'
    );
});
