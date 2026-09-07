<?php

declare(strict_types=1);

namespace Tests\Config\Source;

use Omega\Config\Source\ArrayConfig;

covers(ArrayConfig::class);

it('should return content', function (): void {
    $content = ['key' => 'value'];
    $source = new ArrayConfig($content);

    expect($source->fetch())->toEqual($content);
});
