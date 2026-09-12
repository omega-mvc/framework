<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Exception;
use Omega\View\Exceptions\YeldSectionNotFoundException;
use Omega\View\Templator;
use Omega\View\Templator\ComponentTemplator;
use Omega\View\TemplatorFinder;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Tests\FixturesPathTrait;
use Throwable;

use function trim;

uses(FixturesPathTrait::class);

covers(ComponentTemplator::class);
covers(Templator::class);
covers(TemplatorFinder::class);
covers(YeldSectionNotFoundException::class);

beforeEach(function (): void {
    $this->templator = new Templator(
        new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/view/')], ['']),
        $this->setFixturePath('/fixtures/view/templator/')
    );
});

it('can render component scope', function (): void {
    $out = $this->templator->templates(
        '{% component(\'component.template\') %}<main>core component</main>{% endcomponent %}'
    );
    expect(trim($out))->toEqual('<html><head></head><body><main>core component</main></body></html>');
});

it('can render nested component scope', function (): void {
    $out = $this->templator->templates(
        '{% component(\'componentnested.template\') %}card with nest{% endcomponent %}'
    );
    expect(trim($out))->toEqual(
        '<html><head></head><body><div class="card">card with nest</div>'
        . PHP_EOL
        . '</body></html>'
    );
});

it('can render component scope multiple', function (): void {
    $out = $this->templator->templates(
        '{% component(\'componentcard.template\') %}oke{% endcomponent %} '
        . '{% component(\'componentcard.template\') %}oke 2 {% endcomponent %}'
    );
    expect(trim($out))->toEqual(
        '<div class="card">oke</div>'
        . PHP_EOL
        . ' <div class="card">oke 2 </div>'
    );
});

it('throw when extend not found', function (): void {
    try {
        $this->templator->templates(
            '{% component(\'notexits.template\') %}<main>core component</main>{% endcomponent %}'
        );
    } catch (Throwable $th) {
        expect($th->getMessage())->toEqual(
            'View file not found: `notexits.template`'
        );
    }
});

it('throw when extend not found yield', function (): void {
    try {
        $this->templator->templates(
            '{% component(\'componentyield.template\') %}<main>core component</main>{% endcomponent %}'
        );
    } catch (Throwable $th) {
        expect($th->getMessage())->toEqual('Yield section not found: `component2.template`');
    }
});

it('can render component using named parameter', function (): void {
    $out = $this->templator->templates(
        '{% component(\'componentnamed.template\', bg:\'bg-red\', size:"md") %}inner text{% endcomponent %}'
    );
    expect(trim($out))->toEqual('<p class="bg-red md">inner text</p>');
});

it('can render component opp a process', function (): void {
    $templator = $this->templator;
    $templator->setComponentNamespace('Tests\\View\\Templator\\');
    $out = $templator->templates(
        '{% component(\'TestClassComponent\', bg:\'bg-red\', size:"md") %}inner text{% endcomponent %}'
    );
    expect(trim($out))->toEqual('<p class="bg-red md">inner text</p>');
});

it('can get dependency view', function (): void {
    $finder    = new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/view/')], ['']);
    $templator = new Templator($finder, $this->setFixturePath('/fixtures/view/templator/'));
    $templator->templates(
        '{% component(\'component.template\') %}<main>core component</main>{% endcomponent %}',
        'test'
    );
    expect($templator->getDependency('test'))->toEqual([
        $finder->find('component.template') => 1,
    ]);
});

it('extract component and params with positional param', function (): void {
    $reflection = new ReflectionClass($this->templator);
    $property   = $reflection->getProperty('finder');
    $property->setAccessible(true);
    /** @var TemplatorFinder $finder */
    $finder     = $property->getValue($this->templator);

    $componentTemplator = new ComponentTemplator($finder, $this->setFixturePath('/fixtures/view/templator/'));

    $method = new ReflectionMethod(ComponentTemplator::class, 'extractComponentAndParams');
    $method->setAccessible(true);

    /** @var array{string, array<int, mixed>} $result */
    $result = $method->invoke($componentTemplator, "'MyComp', 'simple'");
    [$name, $params] = $result;

    expect($name)->toEqual('MyComp');
    expect($params)->toEqual(['simple']);
});
