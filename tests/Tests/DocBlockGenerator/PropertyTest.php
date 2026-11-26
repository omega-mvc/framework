<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator;

use Omega\DocBlockGenerator\Property;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

#[CoversClass(Property::class)]
final class PropertyTest extends TestCase
{
    public function testToStringReturnsGeneratedProperty(): void
    {
        $property = Property::new('TO_STRING');

        $this->assertSame("private \$TO_STRING;", (string) $property);
    }

    public function testNameRenamesPropertyAndReturnsSameInstance(): void
    {
        $property = Property::new('OLD');

        $this->assertSame($property, $property->name('NEW'));
        $this->assertSame("private \$NEW;", $property->generate());
    }

    public function testSetStaticTogglesModifierAndReturnsSameInstance(): void
    {
        $property = Property::new('foo');

        $this->assertSame($property, $property->setStatic());
        $this->assertSame("private static \$foo;", $property->generate());

        $this->assertSame($property, $property->setStatic(false));
        $this->assertSame("private \$foo;", $property->generate());
    }

    public function testDataTypeAppendsTrailingSpaceAndReturnsSameInstance(): void
    {
        $property = Property::new('foo');

        $this->assertSame($property, $property->dataType('string'));
        $this->assertSame("private string \$foo;", $property->generate());
    }

    public function testExpectingAcceptsStringAndArrayAndReturnsSameInstance(): void
    {
        $property = Property::new('foo');

        $this->assertSame($property, $property->expecting('= 1'));
        $this->assertSame("private \$foo = 1;", $property->generate());

        $this->assertSame($property, $property->expecting(['= 1', '= 2']));
        $this->assertSame("private \$foo = 1\n\t= 2;", $property->generate());
    }

    public function testGenerateUsesCustomTemplate(): void
    {
        $property = Property::new('foo')->customizeTemplate('{{name}}={{expecting}}');

        $this->assertSame("\$foo=", $property->generate());
    }

    public function testGenerateWithMultipleComments(): void
    {
        $property = Property::new('foo')
            ->addComment('first')
            ->addComment('second');

        $this->assertSame(
            "/**\n\t * first\n\t * second\n\t */\n\tprivate \$foo;",
            $property->generate()
        );
    }

    /**
     * @return iterable<string, array{int, int|null, int, int, string}>
     */
    public static function provideGenerateCases(): iterable
    {
        yield 'comment=0 visibility=default static=0 expecting=0' => [0, null, 0, 0, "private \$foo;"];
        yield 'comment=0 visibility=default static=0 expecting=1' => [0, null, 0, 1, "private \$foo = 1;"];
        yield 'comment=0 visibility=default static=0 expecting=2' => [0, null, 0, 2, "private \$foo = 1\n\t= 2;"];
        yield 'comment=0 visibility=default static=1 expecting=0' => [0, null, 1, 0, "private static \$foo;"];
        yield 'comment=0 visibility=default static=1 expecting=1' => [0, null, 1, 1, "private static \$foo = 1;"];
        yield 'comment=0 visibility=default static=1 expecting=2' => [
            0, null, 1, 2, "private static \$foo = 1\n\t= 2;",
        ];
        yield 'comment=0 visibility=public static=0 expecting=0' => [0, 0, 0, 0, "public \$foo;"];
        yield 'comment=0 visibility=public static=0 expecting=1' => [0, 0, 0, 1, "public \$foo = 1;"];
        yield 'comment=0 visibility=public static=0 expecting=2' => [0, 0, 0, 2, "public \$foo = 1\n\t= 2;"];
        yield 'comment=0 visibility=public static=1 expecting=0' => [0, 0, 1, 0, "public static \$foo;"];
        yield 'comment=0 visibility=public static=1 expecting=1' => [0, 0, 1, 1, "public static \$foo = 1;"];
        yield 'comment=0 visibility=public static=1 expecting=2' => [0, 0, 1, 2, "public static \$foo = 1\n\t= 2;"];
        yield 'comment=0 visibility=protected static=0 expecting=0' => [0, 2, 0, 0, "protected \$foo;"];
        yield 'comment=0 visibility=protected static=0 expecting=1' => [0, 2, 0, 1, "protected \$foo = 1;"];
        yield 'comment=0 visibility=protected static=0 expecting=2' => [0, 2, 0, 2, "protected \$foo = 1\n\t= 2;"];
        yield 'comment=0 visibility=protected static=1 expecting=0' => [0, 2, 1, 0, "protected static \$foo;"];
        yield 'comment=0 visibility=protected static=1 expecting=1' => [0, 2, 1, 1, "protected static \$foo = 1;"];
        yield 'comment=0 visibility=protected static=1 expecting=2' => [
            0, 2, 1, 2, "protected static \$foo = 1\n\t= 2;",
        ];
        yield 'comment=0 visibility=private static=0 expecting=0' => [0, 1, 0, 0, "private \$foo;"];
        yield 'comment=0 visibility=private static=0 expecting=1' => [0, 1, 0, 1, "private \$foo = 1;"];
        yield 'comment=0 visibility=private static=0 expecting=2' => [0, 1, 0, 2, "private \$foo = 1\n\t= 2;"];
        yield 'comment=0 visibility=private static=1 expecting=0' => [0, 1, 1, 0, "private static \$foo;"];
        yield 'comment=0 visibility=private static=1 expecting=1' => [0, 1, 1, 1, "private static \$foo = 1;"];
        yield 'comment=0 visibility=private static=1 expecting=2' => [0, 1, 1, 2, "private static \$foo = 1\n\t= 2;"];
        yield 'comment=1 visibility=default static=0 expecting=0' => [
            1, null, 0, 0, "/** a comment */\n\tprivate \$foo;",
        ];
        yield 'comment=1 visibility=default static=0 expecting=1' => [
            1, null, 0, 1, "/** a comment */\n\tprivate \$foo = 1;",
        ];
        yield 'comment=1 visibility=default static=0 expecting=2' => [
            1, null, 0, 2, "/** a comment */\n\tprivate \$foo = 1\n\t= 2;",
        ];
        yield 'comment=1 visibility=default static=1 expecting=0' => [
            1, null, 1, 0, "/** a comment */\n\tprivate static \$foo;",
        ];
        yield 'comment=1 visibility=default static=1 expecting=1' => [
            1, null, 1, 1, "/** a comment */\n\tprivate static \$foo = 1;",
        ];
        yield 'comment=1 visibility=default static=1 expecting=2' => [
            1, null, 1, 2, "/** a comment */\n\tprivate static \$foo = 1\n\t= 2;",
        ];
        yield 'comment=1 visibility=public static=0 expecting=0' => [1, 0, 0, 0, "/** a comment */\n\tpublic \$foo;"];
        yield 'comment=1 visibility=public static=0 expecting=1' => [
            1, 0, 0, 1, "/** a comment */\n\tpublic \$foo = 1;",
        ];
        yield 'comment=1 visibility=public static=0 expecting=2' => [
            1, 0, 0, 2, "/** a comment */\n\tpublic \$foo = 1\n\t= 2;",
        ];
        yield 'comment=1 visibility=public static=1 expecting=0' => [
            1, 0, 1, 0, "/** a comment */\n\tpublic static \$foo;",
        ];
        yield 'comment=1 visibility=public static=1 expecting=1' => [
            1, 0, 1, 1, "/** a comment */\n\tpublic static \$foo = 1;",
        ];
        yield 'comment=1 visibility=public static=1 expecting=2' => [
            1, 0, 1, 2, "/** a comment */\n\tpublic static \$foo = 1\n\t= 2;",
        ];
        yield 'comment=1 visibility=protected static=0 expecting=0' => [
            1, 2, 0, 0, "/** a comment */\n\tprotected \$foo;",
        ];
        yield 'comment=1 visibility=protected static=0 expecting=1' => [
            1, 2, 0, 1, "/** a comment */\n\tprotected \$foo = 1;",
        ];
        yield 'comment=1 visibility=protected static=0 expecting=2' => [
            1, 2, 0, 2, "/** a comment */\n\tprotected \$foo = 1\n\t= 2;",
        ];
        yield 'comment=1 visibility=protected static=1 expecting=0' => [
            1, 2, 1, 0, "/** a comment */\n\tprotected static \$foo;",
        ];
        yield 'comment=1 visibility=protected static=1 expecting=1' => [
            1, 2, 1, 1, "/** a comment */\n\tprotected static \$foo = 1;",
        ];
        yield 'comment=1 visibility=protected static=1 expecting=2' => [
            1, 2, 1, 2, "/** a comment */\n\tprotected static \$foo = 1\n\t= 2;",
        ];
        yield 'comment=1 visibility=private static=0 expecting=0' => [1, 1, 0, 0, "/** a comment */\n\tprivate \$foo;"];
        yield 'comment=1 visibility=private static=0 expecting=1' => [
            1, 1, 0, 1, "/** a comment */\n\tprivate \$foo = 1;",
        ];
        yield 'comment=1 visibility=private static=0 expecting=2' => [
            1, 1, 0, 2, "/** a comment */\n\tprivate \$foo = 1\n\t= 2;",
        ];
        yield 'comment=1 visibility=private static=1 expecting=0' => [
            1, 1, 1, 0, "/** a comment */\n\tprivate static \$foo;",
        ];
        yield 'comment=1 visibility=private static=1 expecting=1' => [
            1, 1, 1, 1, "/** a comment */\n\tprivate static \$foo = 1;",
        ];
        yield 'comment=1 visibility=private static=1 expecting=2' => [
            1, 1, 1, 2, "/** a comment */\n\tprivate static \$foo = 1\n\t= 2;",
        ];
    }

    #[DataProvider('provideGenerateCases')]
    public function testGenerateCoversEveryPath(
        int $withComment,
        ?int $visibility,
        int $isStatic,
        int $expectingMode,
        string $expected
    ): void {
        $property = Property::new('foo');

        if (1 === $withComment) {
            $property->addComment('a comment');
        }

        if (null !== $visibility) {
            $property->visibility($visibility);
        }

        $property->setStatic(1 === $isStatic);

        if (1 === $expectingMode) {
            $property->expecting('= 1');
        } elseif (2 === $expectingMode) {
            $property->expecting(['= 1', '= 2']);
        }

        $this->assertSame($expected, $property->generate());
    }
}
