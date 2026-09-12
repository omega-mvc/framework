<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Exception;
use Omega\View\Exceptions\DirectiveCanNotBeRegisterException;
use Omega\View\Exceptions\DirectiveNotRegisterException;
use Omega\View\Templator;
use Omega\View\Templator\DirectiveTemplator;
use Omega\View\TemplatorFinder;
use Tests\FixturesPathTrait;

uses(FixturesPathTrait::class);

covers(DirectiveCanNotBeRegisterException::class);
covers(DirectiveNotRegisterException::class);
covers(Templator::class);
covers(DirectiveTemplator::class);
covers(TemplatorFinder::class);

beforeEach(function (): void {
    $this->templator = new Templator(
        new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/view/')], ['']),
        $this->setFixturePath('/fixtures/view/templator/')
    );
});

it('can render each break', function (): void {
    DirectiveTemplator::register('sum', fn (int $a, int $b): int => $a + $b);
    $out = $this->templator->templates('<html><head></head><body>{% sum(1, 2) %}</body></html>');
    expect($out)->toEqual(
        "<html><head></head><body>"
        . "<?php echo Omega\View\Templator\DirectiveTemplator::call('sum', 1, 2); ?>"
        . "</body></html>"
    );
});

it('throw exception due directive not register', function (): void {
    $this->expectException(DirectiveNotRegisterException::class);
    DirectiveTemplator::call('unknow', 0);
});

it('can not register directive', function (): void {
    $this->expectException(DirectiveCanNotBeRegisterException::class);
    DirectiveTemplator::register('include', fn (string $file): string => $file);
});

it('can register and call directive', function (): void {
    DirectiveTemplator::register('sum', fn (int $a, int $b): int => $a + $b);
    expect(DirectiveTemplator::call('sum', 1, 1))->toEqual(2);
});

it('skips reserved directive in parse', function (): void {
    $finder = new TemplatorFinder([]);
    $directive = new DirectiveTemplator($finder, '/tmp');

    $template = '{% include("file") %}';
    $out = $directive->parse($template);

    expect($out)->toEqual('{% include("file") %}');
});
