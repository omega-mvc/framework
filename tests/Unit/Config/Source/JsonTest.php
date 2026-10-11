<?php

declare(strict_types=1);

namespace Tests\Config\Source;

use Omega\Config\Exceptions\MalformedJsonException;
use Omega\Config\Source\JsonConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(MalformedJsonException::class)]
#[CoversClass(JsonConfig::class)]
final class JsonTest extends TestCase
{
    public function testShouldReturnValues(): void
    {
        $source = new JsonConfig(__DIR__ . '/../fixtures/config/content.json');

        $this->assertEquals(['key' => 'value'], $source->fetch());
    }

    public function testShouldThrowOnMalformedConfiguration(): void
    {
        $source = new JsonConfig(__DIR__ . '/../fixtures/config/malformed.json');

        $this->expectException(MalformedJsonException::class);

        $source->fetch();
    }
}
