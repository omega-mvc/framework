<?php

declare(strict_types=1);

namespace Tests\Config\Source;

use Omega\Config\Exceptions\MalformedJsonException;
use Omega\Config\Source\JsonConfig;

covers(MalformedJsonException::class);
covers(JsonConfig::class);


it('should return values', function (): void {
    $source = new JsonConfig(__DIR__ . '/../fixtures/config/content.json');

    expect($source->fetch())->toEqual(['key' => 'value']);
});

it('should throw on malformed configuration', function (): void {
    $source = new JsonConfig(__DIR__ . '/../fixtures/config/malformed.json');

    $source->fetch();
})->throws(MalformedJsonException::class);
