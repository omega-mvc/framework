<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator;

use Omega\DocBlockGenerator\Constant;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

#[CoversClass(Constant::class)]
final class ConstantTest extends TestCase
{
    public function testToStringReturnsGeneratedConstant(): void
    {
        $constant = Constant::new('TO_STRING');

        $this->assertSame('const TO_STRING = null;', (string) $constant);
    }

    public function testNameRenamesConstantAndReturnsSameInstance(): void
    {
        $constant = Constant::new('OLD');

        $this->assertSame($constant, $constant->name('NEW'));
        $this->assertSame('const NEW = null;', $constant->generate());
    }

    public function testExpectingSetsRawAssignment(): void
    {
        $constant = Constant::new('RAW');

        $this->assertSame($constant, $constant->expecting('= 456'));
        $this->assertSame('const RAW = 456;', $constant->generate());
    }

    /**
     * @return iterable<string, array{int|null, bool, bool, string}>
     */
    public static function provideGenerateCases(): iterable
    {
        yield 'default without comment without value' => [null, false, false, 'const MY_CONST = null;'];
        yield 'default without comment with value' => [null, false, true, 'const MY_CONST = 123;'];
        yield 'default with comment without value' => [null, true, false, "/** a comment */\n\tconst MY_CONST = null;"];
        yield 'default with comment with value' => [null, true, true, "/** a comment */\n\tconst MY_CONST = 123;"];

        yield 'public without comment without value' => [
            Constant::PUBLIC_, false, false, 'public const MY_CONST = null;',
        ];
        yield 'public without comment with value' => [Constant::PUBLIC_, false, true, 'public const MY_CONST = 123;'];
        yield 'public with comment without value' => [
            Constant::PUBLIC_, true, false, "/** a comment */\n\tpublic const MY_CONST = null;",
        ];
        yield 'public with comment with value' => [
            Constant::PUBLIC_, true, true, "/** a comment */\n\tpublic const MY_CONST = 123;",
        ];

        yield 'protected without comment without value' => [
            Constant::PROTECTED_, false, false, 'protected const MY_CONST = null;',
        ];
        yield 'protected without comment with value' => [
            Constant::PROTECTED_, false, true, 'protected const MY_CONST = 123;',
        ];
        yield 'protected with comment without value' => [
            Constant::PROTECTED_, true, false, "/** a comment */\n\tprotected const MY_CONST = null;",
        ];
        yield 'protected with comment with value' => [
            Constant::PROTECTED_, true, true, "/** a comment */\n\tprotected const MY_CONST = 123;",
        ];

        yield 'private without comment without value' => [
            Constant::PRIVATE_, false, false, 'private const MY_CONST = null;',
        ];
        yield 'private without comment with value' => [
            Constant::PRIVATE_, false, true, 'private const MY_CONST = 123;',
        ];
        yield 'private with comment without value' => [
            Constant::PRIVATE_, true, false, "/** a comment */\n\tprivate const MY_CONST = null;",
        ];
        yield 'private with comment with value' => [
            Constant::PRIVATE_, true, true, "/** a comment */\n\tprivate const MY_CONST = 123;",
        ];
    }

    #[DataProvider('provideGenerateCases')]
    public function testGenerateCoversEveryPath(
        ?int $visibility,
        bool $withComment,
        bool $withValue,
        string $expected
    ): void {
        $constant = Constant::new('MY_CONST');

        if (null !== $visibility) {
            $constant->visibility($visibility);
        }

        if ($withComment) {
            $constant->addComment('a comment');
        }

        if ($withValue) {
            $constant->equal('123');
        }

        $this->assertSame($expected, $constant->generate());
    }

    public function testGenerateUsesCustomTemplate(): void
    {
        $constant = Constant::new('CUSTOM')->customizeTemplate('{{name}}={{expecting}}');

        $this->assertSame('CUSTOM= = null', $constant->generate());
    }

    public function testGenerateWithMultipleComments(): void
    {
        $constant = Constant::new('MULTI')
            ->addComment('first')
            ->addComment('second')
            ->equal('1');

        $this->assertSame("/**\n\t * first\n\t * second\n\t */\n\tconst MULTI = 1;", $constant->generate());
    }
}
