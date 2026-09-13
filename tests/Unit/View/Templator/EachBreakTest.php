<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Exception;
use Omega\View\Templator;
use Omega\View\Templator\BreakTemplator;
use Omega\View\Templator\EachTemplator;
use Omega\View\TemplatorFinder;


covers(BreakTemplator::class);
covers(EachTemplator::class);
covers(Templator::class);
covers(TemplatorFinder::class);

beforeEach(function (): void {
    $this->templator = new Templator(
        new TemplatorFinder([__DIR__ . '/../fixtures/view/templator/view/'], ['']),
        __DIR__ . '/../fixtures/view/templator/'
    );
});

it('can render each break', function (): void {
    $out = $this->templator->templates(
        '<html><head></head><body>{% foreach ($numbers as $number) %}{% break %}{% endforeach %}</body></html>'
    );
    expect($out)->toEqual(
        '<html><head></head><body>'
        . '<?php foreach ($numbers as $number): ?>'
        . '<?php break ; ?>'
        . '<?php endforeach; ?>'
        . '</body></html>'
    );
});
