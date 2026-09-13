<?php

declare(strict_types=1);

namespace Tests\Config\Source;

use Omega\Config\Exceptions\FileReadException;

covers(FileReadException::class);


it('should fetch file content', function (): void {
    $source = new TestConfigurationSource(__DIR__ . '/../fixtures/config/content.txt');

    expect($source->fetch())->toEqual(['content' => 'content']);
});

it('should throw if file not readable', function (): void {
    new TestConfigurationSource(__DIR__ . '/../fixtures/config/not-found.txt')->fetch();
})->throws(FileReadException::class);
