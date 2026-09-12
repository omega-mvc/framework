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
        new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/')], ['']),
        $this->setFixturePath('/fixtures/view/templator/')
    );
});

it('can render each', function (): void {
    $out = $this->templator->templates('{% foreach ($numbers as $number) %}{{ $number }}{% endforeach %}');
    expect($out)->toEqual(
        '<?php foreach ($numbers as $number): ?><?php echo htmlspecialchars($number); ?><?php endforeach; ?>'
    );
});

it('can render each without curve braces', function (): void {
    $out = $this->templator->templates('{% foreach $numbers as $number %}{{ $number }}{% endforeach %}');
    expect($out)->toEqual(
        '<?php foreach ($numbers as $number): ?><?php echo htmlspecialchars($number); ?><?php endforeach; ?>'
    );
});

it('can render each with key value', function (): void {
    $out = $this->templator->templates('{% foreach ($numbers as $key => $number) %}{{ $number }}{% endforeach %}');
    expect($out)->toEqual(
        '<?php foreach ($numbers as $key => $number): ?>'
        . '<?php echo htmlspecialchars($number); ?>'
        . '<?php endforeach; ?>'
    );
});

it('can render nested each', function (): void {
    $template = '{% foreach ($categories as $category) %}{{ $category->name }}'
        . '{% foreach ($category->items as $item) %}{{ $item->name }}{% endforeach %}{% endforeach %}';

    $expected = '<?php foreach ($categories as $category): ?>'
        . '<?php echo htmlspecialchars($category->name); ?>'
        . '<?php foreach ($category->items as $item): ?>'
        . '<?php echo htmlspecialchars($item->name); ?>'
        . '<?php endforeach; ?>'
        . '<?php endforeach; ?>';

    $out = $this->templator->templates($template);
    expect($out)->toEqual($expected);
});

it('can render nested each with key value', function (): void {
    $template = '{% foreach ($data as $key => $values) %}{{ $key }}'
        . '{% foreach ($values as $index => $item) %}{{ $index }}: {{ $item }}'
        . '{% endforeach %}{% endforeach %}';
    $expected = '<?php foreach ($data as $key => $values): ?>'
        . '<?php echo htmlspecialchars($key); ?>'
        . '<?php foreach ($values as $index => $item): ?>'
        . '<?php echo htmlspecialchars($index); ?>: '
        . '<?php echo htmlspecialchars($item); ?>'
        . '<?php endforeach; ?>'
        . '<?php endforeach; ?>';

    $out = $this->templator->templates($template);
    expect($out)->toEqual($expected);
});

it('can render multiple foreach blocks', function (): void {
    $template = '{% foreach ($users as $user) %}{{ $user->name }}{% endforeach %}'
        . '{% foreach ($products as $product) %}{{ $product->name }}{% endforeach %}';
    $expected = '<?php foreach ($users as $user): ?>'
        . '<?php echo htmlspecialchars($user->name); ?>'
        . '<?php endforeach; ?>'
        . '<?php foreach ($products as $product): ?>'
        . '<?php echo htmlspecialchars($product->name); ?>'
        . '<?php endforeach; ?>';

    $out = $this->templator->templates($template);
    expect($out)->toEqual($expected);
});
