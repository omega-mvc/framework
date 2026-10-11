<?php

declare(strict_types=1);

namespace Tests\Console\Attribute;

use Omega\Console\Attribute\AsCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(AsCommand::class)]
final class AsCommandTest extends TestCase
{
    public function testExposesTheCommandName(): void
    {
        $attribute = new AsCommand('demo:command');

        $this->assertSame('demo:command', $attribute->name);
    }

    public function testAppliesTheDefaultOptionalValues(): void
    {
        $attribute = new AsCommand('demo:command');

        $this->assertNull($attribute->description);
        $this->assertSame([], $attribute->arguments);
        $this->assertSame([], $attribute->options);
        $this->assertSame([], $attribute->aliases);
        $this->assertFalse($attribute->hidden);
    }

    public function testStoresTheFullConfiguration(): void
    {
        $attribute = new AsCommand(
            'demo:command',
            'A demonstration command.',
            ['name' => [2, 'The name']],
            ['greet' => ['g', 1, 'Greet']],
            ['demo:c'],
            true,
        );

        $this->assertSame('demo:command', $attribute->name);
        $this->assertSame('A demonstration command.', $attribute->description);
        $this->assertSame(['name' => [2, 'The name']], $attribute->arguments);
        $this->assertSame(['greet' => ['g', 1, 'Greet']], $attribute->options);
        $this->assertSame(['demo:c'], $attribute->aliases);
        $this->assertTrue($attribute->hidden);
    }
}
