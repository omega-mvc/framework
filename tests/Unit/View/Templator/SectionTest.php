<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Exception;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use Omega\View\Templator\SectionTemplator;
use Tests\FixturesPathTrait;
use Throwable;

use function trim;

use const PHP_EOL;

uses(FixturesPathTrait::class);

covers(SectionTemplator::class);
covers(Templator::class);
covers(TemplatorFinder::class);

beforeEach(function (): void {
    $this->templator = new Templator(
        new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/view/')], ['']),
        $this->setFixturePath('/fixtures/view/templator/')
    );
});

it('can render section scope', function (): void {
    $out = $this->templator->templates(
        '{% extend(\'section.template\') %} {% section(\'title\') %}<strong>taylor</strong>{% endsection %}'
    );
    expect(trim($out))->toEqual('<p><strong>taylor</strong></p>');
});

it('throw when extend not found', function (): void {
    try {
        $this->templator->templates(
            '{% extend(\'section.html\') %} {% section(\'title\') %}<strong>taylor</strong>{% endsection %}'
        );
    } catch (Throwable $th) {
        expect($th->getMessage())->toEqual('View file not found: `section.html`');
    }
});

it('can render section inline', function (): void {
    $out = $this->templator->templates('{% extend(\'section.template\') %} {% section(\'title\', \'taylor\') %}');
    expect(trim($out))->toEqual('<p>taylor</p>');
});

it('can render section inline escape', function (): void {
    $out = $this->templator->templates(
        '{% extend(\'section.template\') %} {% section(\'title\', \'<script>alert(1)</script>\') %}'
    );
    expect(trim($out))->toEqual('<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>');
});

it('can render multi section', function (): void {
    $out = $this->templator->templates('
            {% extend(\'section.template\') %}

            {% sections %}
            title : <strong>taylor</strong>
            {% endsections %}
        ');
    expect(trim($out))->toEqual('<p><strong>taylor</strong></p>');
});

it('can get dependency view', function (): void {
    $finder    = new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/view/')], ['']);
    $templator = new Templator($finder, $this->setFixturePath('/fixtures/view/templator'));
    $templator->templates(
        '{% extend(\'section.template\') %} {% section(\'title\') %}<strong>taylor</strong>{% endsection %}',
        'test'
    );
    expect($templator->getDependency('test'))->toEqual([
        $finder->find('section.template') => 1,
    ]);
});

it('can render section scope with default yield', function (): void {
    $out = $this->templator->templates('{% extend(\'sectiondefault.template\') %}');
    expect(trim($out))->toEqual('<p>nuno</p>');
});

it('can render section with multi line', function (): void {
    $out = $this->templator->templates('{% extend(\'sectiondefaultmultilines.template\') %}');
    expect(trim($out))->toEqual(
        '<li>'
        . PHP_EOL
        . '<ul>one</ul>'
        . PHP_EOL
        . '<ul>two</ul>'
        . PHP_EOL
        . '<ul>three</ul>'
        . PHP_EOL
        . '</li>'
    );
});

it('will throw error have two default', function (): void {
    $this->expectExceptionMessageIsOrContains('The yield statement cannot have both a default value and content.');
    $this->templator->templates('{% extend(\'sectiondefaultandmultilines.template\') %}');
});

it('returns template if no extend', function (): void {
    $template = '<p>no extend here</p>';
    $out      = $this->templator->templates($template);

    expect($out)->toEqual('<p>no extend here</p>');
});

it('throws when required yield section missing', function (): void {
    $this->expectExceptionMessageIsOrContains(
        "Slot with extends 'sectionwithmissingyield.template' required 'missing_section'"
    );

    $childTemplate  = '{% extend(\'sectionwithmissingyield.template\') %}';
    $this->templator->templates($childTemplate);
});

it('returns empty string when yield not defined', function (): void {
    $layoutPath = $this->setFixturePath('/fixtures/view/templator/view/sectionempty.template');

    file_put_contents($layoutPath, '{% yield %}');

    $out = $this->templator->templates('{% extend("sectionempty.template") %}');

    expect(trim($out))->toEqual('');
});
