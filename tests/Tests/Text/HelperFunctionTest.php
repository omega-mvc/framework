<?php

declare(strict_types=1);

namespace Tests\Text;

use Omega\Text\Text;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use Tests\TestCase;

use function Omega\Text\string;
use function Omega\Text\text;

#[CoversClass(Text::class)]
#[CoversFunction('Omega\Text\string')]
#[CoversFunction('Omega\Text\text')]
final class HelperFunctionTest extends TestCase
{
    public function testCreatesATextInstanceWithStringHelper(): void
    {
        $instance = string('hello');

        $this->assertSame(Text::class, $instance::class);
    }

    public function testStringHelperWrapsTheGivenString(): void
    {
        $this->assertSame('hello', (string) string('hello'));
    }

    public function testStringHelperIsFluent(): void
    {
        $this->assertSame('HELLO', (string) string('hello')->upper());
    }

    public function testCreatesATextInstanceWithTextHelper(): void
    {
        $instance = text('hello');

        $this->assertSame(Text::class, $instance::class);
    }

    public function testTextHelperWrapsTheGivenString(): void
    {
        $this->assertSame('hello', (string) text('hello'));
    }

    public function testTextHelperIsFluent(): void
    {
        $this->assertSame('HELLO', (string) text('hello')->upper());
    }
}
