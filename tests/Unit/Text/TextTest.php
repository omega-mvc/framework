<?php

declare(strict_types=1);

namespace Tests\Text;

use Omega\Text\Str;
use Omega\Text\Text;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function Omega\Text\string;
use function Omega\Text\text;

#[CoversClass(Str::class)]
#[CoversClass(Text::class)]
final class TextTest extends TestCase
{
    public function testCanCreateNewInstanceUsingHelper(): void
    {
        $this->assertSame('text', string('text')->getText());
        $this->assertSame('text', text('text')->getText());
    }

    public function testCanCreateNewInstanceUsingStrClass(): void
    {
        $this->assertSame('text', Str::of('text')->getText());
    }

    public function testCanSetGetCurrentText(): void
    {
        $class = new Text('text');

        $this->assertSame('text', $class->getText());
    }

    public function testCanSetGetCurrentTextUsingToString(): void
    {
        $class = new Text('text');

        $this->assertEquals('text', $class);
    }

    public function testCanSetNewTextWithoutReset(): void
    {
        $class = new Text('text');
        $class->upper()->lower()->firstUpper();
        $class->text('string');

        $this->assertSame('string', $class->getText());
        $this->assertCount(5, $class->logs());
    }

    public function testCanSetGetLogOfString(): void
    {
        $class = new Text('text');
        $class->upper()->lower()->firstUpper();

        $logs = $class->logs();
        $this->assertCount(4, $logs);

        foreach ($logs as $log) {
            $this->assertArrayHasKey('function', $log);
            $this->assertArrayHasKey('return', $log);
            $this->assertArrayHasKey('type', $log);
        }
    }

    public function testCanSetReset(): void
    {
        $class = new Text('text');
        $class->upper()->lower()->firstUpper();
        $class->reset();

        $this->assertSame('text', $class->getText());
        $this->assertEmpty($class->logs());
    }

    public function testCanSetRefresh(): void
    {
        $class = new Text('text');
        $class->upper()->lower()->firstUpper();
        $class->refresh('string');

        $this->assertSame('string', $class->getText());
        $this->assertEmpty($class->logs());
    }

    public function testCanChainNonStringAndContinueChainWithoutBreak(): void
    {
        $class = new Text('text');
        $class->upper()->firstUpper();

        $this->assertTrue($class->startsWith('T'));
        $this->assertSame(4, $class->length());

        $class->lower();
        $this->assertTrue($class->startsWith('t'));
    }
}
