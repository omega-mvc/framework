<?php

declare(strict_types=1);

namespace Tests\Config\Source;

use Omega\Config\Source\ArrayConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(ArrayConfig::class)]
final class ArrayTest extends TestCase
{
    public function testShouldReturnContent(): void
    {
        $content = ['key' => 'value'];
        $source = new ArrayConfig($content);

        $this->assertEquals($content, $source->fetch());
    }
}
