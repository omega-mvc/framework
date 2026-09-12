<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Exception;
use Omega\View\Templator;
use Omega\View\Templator\NameTemplator;
use Omega\View\TemplatorFinder;
use Tests\FixturesPathTrait;

uses(FixturesPathTrait::class);

covers(NameTemplator::class);
covers(Templator::class);
covers(TemplatorFinder::class);

beforeEach(function (): void {
    $this->templator = new Templator(
        new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/')], ['']),
        $this->setFixturePath('/fixtures/view/templator/')
    );
});

it('can render naming', function (): void {
    $out = $this->templator->templates(
        '<html><head></head><body><h1>your {{ $name }}, ages {{ $age }} </h1></body></html>'
    );
    expect($out)->toEqual(
        '<html><head></head><body><h1>your <?php echo htmlspecialchars($name); ?>, '
        . 'ages <?php echo htmlspecialchars($age); ?> </h1></body></html>'
    );
});

it('can render naming without escape', function (): void {
    $out = $this->templator->templates(
        '<html><head></head><body><h1>your {!! $name !!}, '
        . 'ages {!! $age !!} </h1></body></html>'
    );
    expect($out)->toEqual(
        '<html><head></head><body><h1>your <?php echo $name; ?>, '
        . 'ages <?php echo $age; ?> </h1></body></html>'
    );
});

it('can render naming with call function', function (): void {
    $out = $this->templator->templates(
        '<html><head></head><body><h1>time: }{{ now()->getTimestamp() }}</h1></body></html>'
    );
    expect($out)->toEqual(
        '<html><head></head><body><h1>time: }<?php echo htmlspecialchars'
        . '(now()->getTimestamp()); ?></h1></body></html>'
    );
});

it('can render naming ternary', function (): void {
    $out = $this->templator->templates(
        '<html><head></head><body><h1>your '
        . '{{ $name ?? \'nuno\' }}, ages '
        . '{{ $age ? 17 : 28 }} </h1></body></html>'
    );
    expect($out)->toEqual(
        '<html><head></head><body><h1>your '
        . '<?php echo htmlspecialchars($name ?? \'nuno\'); ?>, ages '
        . '<?php echo htmlspecialchars($age ? 17 : 28); ?> </h1>'
        . '</body></html>'
    );
});

it('can render naming skip', function (): void {
    $out = $this->templator->templates(
        '<html><head></head><body><h1>{{ $render }}, '
        . '{% raw %}your {{ name }}, ages {{ age }}{% endraw %}</h1></body></html>'
    );
    expect($out)->toEqual(
        '<html><head></head><body><h1><?php echo htmlspecialchars($render); ?>, '
        . 'your {{ name }}, ages {{ age }}</h1></body></html>'
    );
});

it('handles empty template without any variables', function (): void {
    $out = $this->templator->templates('');
    expect($out)->toEqual('');
});

it('handles template with only raw blocks', function (): void {
    $template = '{% raw %}RAW_CONTENT{% endraw %}';
    $out = $this->templator->templates($template);
    expect($out)->toEqual('RAW_CONTENT');
});

it('handles multiple raw and variables', function (): void {
    $template = '{% raw %}BLOCK1{% endraw %} {{ $var }} {% raw %}BLOCK2{% endraw %}';
    $out = $this->templator->templates($template);
    $expected = 'BLOCK1 <?php echo htmlspecialchars($var); ?> BLOCK2';
    expect($out)->toEqual($expected);
});
