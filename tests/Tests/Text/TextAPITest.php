<?php

declare(strict_types=1);

namespace Tests\Text;

use Omega\Text\Exceptions\NoReturnException;
use Omega\Text\Text;
use ReflectionMethod;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class TextAPITest extends TestCase
{
    private Text $text;

    protected function setUp(): void
    {
        parent::setUp();

        $this->text = new Text('i love symfony');
    }

    protected function tearDown(): void
    {
        $this->text->reset();

        parent::tearDown();
    }

    public function testCanReturnChartAt(): void
    {
        $this->assertSame('o', (string) $this->text->charAt(3));
    }

    public function testExecutesWithANonStringValue(): void
    {
        $method = new ReflectionMethod(Text::class, 'execute');

        $method->invoke($this->text, ['foo'], 'test');

        $this->assertCount(2, $this->text->logs());
    }

    public function testCanReturnSlice(): void
    {
        $this->assertSame('symfony', (string) $this->text->slice(7));
    }

    public function testDoesNotThrowWhenSliceIsNotEmptyAndThrowOnFailureIsEnabled(): void
    {
        $this->assertSame(' love symfony', (string) $this->text->throwOnFailure(true)->slice(1));
    }

    public function testThrowsWhenSliceReturnsEmptyAndThrowOnFailureIsEnabled(): void
    {
        $this->expectException(NoReturnException::class);
        $this->expectExceptionMessage('did not return anything');

        $this->text->throwOnFailure(true)->slice(0, 0);
    }

    public function testCanReturnLower(): void
    {
        $this->assertSame('i love symfony', (string) $this->text->lower());
    }

    public function testCanReturnUpper(): void
    {
        $this->assertSame('I LOVE SYMFONY', (string) $this->text->upper());
    }

    public function testCanReturnFirstUpper(): void
    {
        $this->assertSame('I love symfony', (string) $this->text->firstUpper());
    }

    public function testCanReturnFirstUpperAll(): void
    {
        $this->assertSame('I Love Symfony', (string) $this->text->firstUpperAll());
    }

    public function testCanReturnSnake(): void
    {
        $this->assertSame('i_love_symfony', (string) $this->text->snake());
    }

    public function testCanReturnKebab(): void
    {
        $this->assertSame('i-love-symfony', (string) $this->text->kebab());
    }

    public function testCanReturnPascal(): void
    {
        $this->assertSame('ILoveSymfony', (string) $this->text->pascal());
    }

    public function testCanReturnCamel(): void
    {
        $this->assertSame('iLoveSymfony', (string) $this->text->camel());
    }

    public function testCanReturnSlug(): void
    {
        $this->assertSame('i-love-symfony', (string) $this->text->slug());
    }

    public function testCanReturnIsEmpty(): void
    {
        $this->assertFalse($this->text->isEmpty());
    }

    public function testCanReturnIs(): void
    {
        $this->assertFalse($this->text->is('/^[A-Za-z]{1}[A-Za-z0-9]{3,16}$/'));
    }

    public function testCanReturnContains(): void
    {
        $this->assertTrue($this->text->contains('love'));
    }

    public function testCanReturnStartsWith(): void
    {
        $this->assertTrue($this->text->startsWith('i love'));
    }

    public function testCanReturnEndsWith(): void
    {
        $this->assertTrue($this->text->endsWith('symfony'));
    }

    public function testCanReturnLength(): void
    {
        $this->assertSame(14, $this->text->length());
    }

    public function testCanReturnIndexOf(): void
    {
        $this->assertSame(7, $this->text->indexOf('symfony'));
    }

    public function testCanReturnLastIndexOf(): void
    {
        $this->assertSame(13, $this->text->lastIndexOf('y'));
    }

    public function testReturnsFalseWhenLastIndexOfIsNotFound(): void
    {
        $this->assertFalse($this->text->lastIndexOf('x'));
    }

    public function testCanReturnIsMatch(): void
    {
        $this->assertTrue($this->text->isMatch('/^i love/'));
    }

    public function testReturnsFalseWhenIsMatchDoesNotMatch(): void
    {
        $this->assertFalse($this->text->isMatch('/^z/'));
    }

    public function testCanReturnFill(): void
    {
        $this->text->text('1234');

        $this->assertSame('001234', (string) $this->text->fill('0', 6));
    }

    public function testCanReturnFillEnd(): void
    {
        $this->text->text('1234');

        $this->assertSame('123400', (string) $this->text->fillEnd('0', 6));
    }

    public function testCanReturnMask(): void
    {
        $this->text->text('laravel');
        $this->assertSame('l****el', (string) $this->text->mask('*', 1, 4));

        $this->text->text('laravel');
        $this->assertSame('l******', (string) $this->text->mask('*', 1));

        $this->text->text('laravel');
        $this->assertSame('lara*el', (string) $this->text->mask('*', -3, 1));

        $this->text->text('laravel');
        $this->assertSame('lara***', (string) $this->text->mask('*', -3));
    }

    public function testCanReturnLimit(): void
    {
        $this->assertSame('i love ...', (string) $this->text->limit(7));
    }

    public function testCanReturnAfterText(): void
    {
        $this->assertSame('symfony', $this->text->after('love ')->__toString());
    }
}
