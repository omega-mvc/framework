<?php

declare(strict_types=1);

namespace Tests\Config\Source;

use Omega\Config\Exceptions\FileReadException;
use Tests\FixturesPathTrait;

covers(FileReadException::class);

uses(FixturesPathTrait::class);

it('should fetch file content', function (): void {
    $source = new TestConfigurationSource($this->setFixturePath('/fixtures/config/content.txt'));

    expect($source->fetch())->toEqual(['content' => 'content']);
});

it('should throw if file not readable', function (): void {
    new TestConfigurationSource($this->setFixturePath('/fixtures/config/not-found.txt'))->fetch();
})->throws(FileReadException::class);