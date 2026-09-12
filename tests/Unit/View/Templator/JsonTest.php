<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Exception;
use Omega\View\Templator;
use Omega\View\Templator\JsonTemplator;
use Omega\View\TemplatorFinder;
use Tests\FixturesPathTrait;

uses(FixturesPathTrait::class);

covers(JsonTemplator::class);
covers(Templator::class);
covers(TemplatorFinder::class);

beforeEach(function (): void {
    $this->templator = new Templator(
        new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/')], ['']),
        $this->setFixturePath('/fixtures/view/templator/')
    );
});

it('can render json', function (): void {
    $out = $this->templator->templates('<html><head></head><body>{% json($data) %}</body></html>');
    expect($out)->toEqual(
        '<html><head></head><body><?php echo json_encode('
        . '$data, 0 | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR, 512'
        . '); ?></body></html>'
    );
});

it('can render json with optional param', function (): void {
    $out = $this->templator->templates('<html><head></head><body>{% json($data, 1, 500) %}</body></html>');
    expect($out)->toEqual(
        '<html><head></head><body><?php echo json_encode('
        . '$data, 1 | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR, 500'
        . '); ?></body></html>'
    );
});
