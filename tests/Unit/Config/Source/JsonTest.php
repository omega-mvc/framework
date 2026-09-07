<?php

declare(strict_types=1);

namespace Tests\Config\Source;

use Omega\Config\Exceptions\MalformedJsonException;
use Omega\Config\Source\JsonConfig;
use Tests\FixturesPathTrait;

covers(MalformedJsonException::class);
covers(JsonConfig::class);

uses(FixturesPathTrait::class);

it('should return values', function (): void {
    $source = new JsonConfig($this->setFixturePath('/fixtures/config/content.json'));

    expect($source->fetch())->toEqual(['key' => 'value']);
});

it('should throw on malformed configuration', function (): void {
    $source = new JsonConfig($this->setFixturePath('/fixtures/config/malformed.json'));

    $source->fetch();
})->throws(MalformedJsonException::class);
