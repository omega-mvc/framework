<?php

namespace Tests\Config\Source;

use Omega\Config\Exceptions\MalformedJsonException;
use Omega\Config\Source\JsonConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MalformedJsonException::class)]
#[CoversClass(JsonConfig::class)]
class JsonTest extends TestCase
{
    public function testShouldReturnValues(): void
    {
        $source = new JsonConfig(slash(path: __DIR__ . '/fixtures/content.json'));

        $this->assertEquals(['key' => 'value'], $source->fetch());
    }

    public function testShouldThrowOnMalformedConfiguration(): void
    {
        $this->expectException(MalformedJsonException::class);

        $source = new JsonConfig(slash(path: __DIR__ . '/fixtures/malformed.json'));
        $source->fetch();
    }
}
