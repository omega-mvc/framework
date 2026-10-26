<?php

declare(strict_types=1);

namespace Tests\Config\Source;

use Omega\Config\Exceptions\InvalidArrayConfigException;
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

    public function testShouldThrowWhenContentIsEmpty(): void
    {
        $this->expectException(InvalidArrayConfigException::class);

        new ArrayConfig([])->fetch();
    }

    public function testShouldThrowWhenContentIsNotAssociative(): void
    {
        $this->expectException(InvalidArrayConfigException::class);
        $this->expectExceptionMessage('Configuration must be an associative array.');

        new ArrayConfig(['first', 'second'])->fetch();
    }
}
