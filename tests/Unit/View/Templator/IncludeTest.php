<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Exception;
use Omega\View\Exceptions\ViewFileNotFoundException;
use Omega\View\Templator;
use Omega\View\Templator\IncludeTemplator;
use Omega\View\TemplatorFinder;
use ReflectionClass;
use Tests\FixturesPathTrait;

uses(FixturesPathTrait::class);

covers(IncludeTemplator::class);
covers(Templator::class);
covers(TemplatorFinder::class);
covers(ViewFileNotFoundException::class);

beforeEach(function (): void {
    $this->templator = new Templator(
        new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/')], ['']),
        $this->setFixturePath('/fixtures/view/templator/')
    );
});

it('can render include', function (): void {
    $out = $this->templator->templates(
        '<html><head></head><body>{% include(\'/view/component.php\') %}</body></html>'
    );
    expect($out)->toEqual('<html><head></head><body><p>Call From Component</p></body></html>');
});

it('can fetch dependency view', function (): void {
    $finder    = new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator')], ['']);
    $templator = new Templator($finder, $this->setFixturePath('/fixtures/view/templator'));
    $templator->templates('<html><head></head><body>{% include(\'view/component.php\') %}</body></html>', 'test');
    expect($templator->getDependency('test'))->toEqual([
        $finder->find('view/component.php') => 1,
    ]);
});

it('throws exception when include not found', function (): void {
    $this->expectException(ViewFileNotFoundException::class);
    $this->expectExceptionMessageIsOrContains('View file not found: `nonexistent.php`');

    $this->templator->templates(
        '<html>{% include(\'nonexistent.php\') %}</html>'
    );
});

it('returns included template when depth zero', function (): void {
    $reflection = new ReflectionClass($this->templator);
    $property = $reflection->getProperty('finder');
    $property->setAccessible(true);
    /** @var TemplatorFinder $finder */
    $finder = $property->getValue($this->templator);

    $includeTemplator = new IncludeTemplator($finder, $this->setFixturePath('/fixtures/view/templator/'));
    $includeTemplator->maksDept(0);

    $template = "{% include('view/component.php') %}";
    $out      = $includeTemplator->parse($template);

    expect($out)->toContain('<p>Call From Component</p>');
});
