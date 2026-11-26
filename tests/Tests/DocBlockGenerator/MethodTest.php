<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator;

use Omega\DocBlockGenerator\Method;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

#[CoversClass(Method::class)]
final class MethodTest extends TestCase
{
    public function testToStringReturnsGeneratedMethod(): void
    {
        $method = Method::new('TO_STRING');

        $this->assertSame("function TO_STRING()\n\t{\n\n\t}", (string) $method);
    }

    public function testNameRenamesMethodAndReturnsSameInstance(): void
    {
        $method = Method::new('OLD');

        $this->assertSame($method, $method->name('NEW'));
        $this->assertSame("function NEW()\n\t{\n\n\t}", $method->generate());
    }

    public function testAddParamsAppendsParametersAndReturnsSameInstance(): void
    {
        $method = Method::new('foo');

        $this->assertSame($method, $method->addParams('int $a')->addParams('string $b'));
        $this->assertSame("function foo(int \$a, string \$b)\n\t{\n\n\t}", $method->generate());
    }

    public function testParamsReplacesAndClearsParametersAndReturnsSameInstance(): void
    {
        $method = Method::new('foo');

        $this->assertSame($method, $method->params(['int $a', 'string $b']));
        $this->assertSame("function foo(int \$a, string \$b)\n\t{\n\n\t}", $method->generate());

        $this->assertSame($method, $method->params(null));
        $this->assertSame("function foo()\n\t{\n\n\t}", $method->generate());
    }

    public function testSetFinalAndSetStaticAndVisibilityComposeModifiers(): void
    {
        $method = Method::new('foo');

        $this->assertSame($method, $method->setFinal()->visibility(Method::PUBLIC_)->setStatic());
        $this->assertSame("final public static function foo()\n\t{\n\n\t}", $method->generate());

        $this->assertSame($method, $method->setFinal(false)->setStatic(false)->visibility());
        $this->assertSame("public function foo()\n\t{\n\n\t}", $method->generate());
    }

    public function testSetReturnTypeAcceptsStringAndNull(): void
    {
        $method = Method::new('foo');

        $this->assertSame($method, $method->setReturnType('int'));
        $this->assertSame("function foo(): int\n\t{\n\n\t}", $method->generate());

        $this->assertSame($method, $method->setReturnType(null));
        $this->assertSame("function foo(): \n\t{\n\n\t}", $method->generate());
    }

    public function testBodyAcceptsStringArrayAndNull(): void
    {
        $method = Method::new('foo');

        $this->assertSame($method, $method->body('return 1;'));
        $this->assertSame("function foo()\n\t{\n\t\treturn 1;\n\t}", $method->generate());

        $this->assertSame($method, $method->body(['$a = 1;', 'return $a;']));
        $this->assertSame("function foo()\n\t{\n\t\t\$a = 1;\n\t\treturn \$a;\n\t}", $method->generate());

        $this->assertSame($method, $method->body(null));
        $this->assertSame("function foo()\n\t{\n\n\t}", $method->generate());
    }

    public function testGenerateUsesCustomTemplate(): void
    {
        $method = Method::new('foo')->customizeTemplate('{{name}}({{params}}){{return type}}');

        $this->assertSame('foo()', $method->generate());
    }

    public function testGenerateWithMultipleComments(): void
    {
        $method = Method::new('foo')
            ->addComment('first')
            ->addComment('second');

        $this->assertSame(
            "/**\n\t * first\n\t * second\n\t */\n\tfunction foo()\n\t{\n\n\t}",
            $method->generate()
        );
    }

    /**
     * @return iterable<string, array{int, int, int|null, int, int, string}>
     */
    public static function provideGenerateCases(): iterable
    {
        yield 'comment=0 final=0 visibility=default static=0 return=0' => [
            0, 0, null, 0, 0, "function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=0 visibility=default static=0 return=1' => [
            0, 0, null, 0, 1, "function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=0 visibility=default static=1 return=0' => [
            0, 0, null, 1, 0, "static function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=0 visibility=default static=1 return=1' => [
            0, 0, null, 1, 1, "static function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=0 visibility=public static=0 return=0' => [
            0, 0, 0, 0, 0, "public function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=0 visibility=public static=0 return=1' => [
            0, 0, 0, 0, 1, "public function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=0 visibility=public static=1 return=0' => [
            0, 0, 0, 1, 0, "public static function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=0 visibility=public static=1 return=1' => [
            0, 0, 0, 1, 1, "public static function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=0 visibility=private static=0 return=0' => [
            0, 0, 1, 0, 0, "private function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=0 visibility=private static=0 return=1' => [
            0, 0, 1, 0, 1, "private function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=0 visibility=private static=1 return=0' => [
            0, 0, 1, 1, 0, "private static function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=0 visibility=private static=1 return=1' => [
            0, 0, 1, 1, 1, "private static function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=0 visibility=protected static=0 return=0' => [
            0, 0, 2, 0, 0, "protected function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=0 visibility=protected static=0 return=1' => [
            0, 0, 2, 0, 1, "protected function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=0 visibility=protected static=1 return=0' => [
            0, 0, 2, 1, 0, "protected static function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=0 visibility=protected static=1 return=1' => [
            0, 0, 2, 1, 1, "protected static function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=default static=0 return=0' => [
            0, 1, null, 0, 0, "final function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=default static=0 return=1' => [
            0, 1, null, 0, 1, "final function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=default static=1 return=0' => [
            0, 1, null, 1, 0, "final static function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=default static=1 return=1' => [
            0, 1, null, 1, 1, "final static function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=public static=0 return=0' => [
            0, 1, 0, 0, 0, "final public function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=public static=0 return=1' => [
            0, 1, 0, 0, 1, "final public function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=public static=1 return=0' => [
            0, 1, 0, 1, 0, "final public static function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=public static=1 return=1' => [
            0, 1, 0, 1, 1, "final public static function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=private static=0 return=0' => [
            0, 1, 1, 0, 0, "final private function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=private static=0 return=1' => [
            0, 1, 1, 0, 1, "final private function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=private static=1 return=0' => [
            0, 1, 1, 1, 0, "final private static function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=private static=1 return=1' => [
            0, 1, 1, 1, 1, "final private static function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=protected static=0 return=0' => [
            0, 1, 2, 0, 0, "final protected function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=protected static=0 return=1' => [
            0, 1, 2, 0, 1, "final protected function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=protected static=1 return=0' => [
            0, 1, 2, 1, 0, "final protected static function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=0 final=1 visibility=protected static=1 return=1' => [
            0, 1, 2, 1, 1, "final protected static function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=default static=0 return=0' => [
            1, 0, null, 0, 0, "/** a comment */\n\tfunction foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=default static=0 return=1' => [
            1, 0, null, 0, 1, "/** a comment */\n\tfunction foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=default static=1 return=0' => [
            1, 0, null, 1, 0, "/** a comment */\n\tstatic function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=default static=1 return=1' => [
            1, 0, null, 1, 1, "/** a comment */\n\tstatic function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=public static=0 return=0' => [
            1, 0, 0, 0, 0, "/** a comment */\n\tpublic function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=public static=0 return=1' => [
            1, 0, 0, 0, 1, "/** a comment */\n\tpublic function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=public static=1 return=0' => [
            1, 0, 0, 1, 0, "/** a comment */\n\tpublic static function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=public static=1 return=1' => [
            1, 0, 0, 1, 1, "/** a comment */\n\tpublic static function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=private static=0 return=0' => [
            1, 0, 1, 0, 0, "/** a comment */\n\tprivate function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=private static=0 return=1' => [
            1, 0, 1, 0, 1, "/** a comment */\n\tprivate function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=private static=1 return=0' => [
            1, 0, 1, 1, 0, "/** a comment */\n\tprivate static function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=private static=1 return=1' => [
            1, 0, 1, 1, 1, "/** a comment */\n\tprivate static function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=protected static=0 return=0' => [
            1, 0, 2, 0, 0, "/** a comment */\n\tprotected function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=protected static=0 return=1' => [
            1, 0, 2, 0, 1, "/** a comment */\n\tprotected function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=protected static=1 return=0' => [
            1, 0, 2, 1, 0, "/** a comment */\n\tprotected static function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=0 visibility=protected static=1 return=1' => [
            1, 0, 2, 1, 1, "/** a comment */\n\tprotected static function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=default static=0 return=0' => [
            1, 1, null, 0, 0, "/** a comment */\n\tfinal function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=default static=0 return=1' => [
            1, 1, null, 0, 1, "/** a comment */\n\tfinal function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=default static=1 return=0' => [
            1, 1, null, 1, 0, "/** a comment */\n\tfinal static function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=default static=1 return=1' => [
            1, 1, null, 1, 1, "/** a comment */\n\tfinal static function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=public static=0 return=0' => [
            1, 1, 0, 0, 0, "/** a comment */\n\tfinal public function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=public static=0 return=1' => [
            1, 1, 0, 0, 1, "/** a comment */\n\tfinal public function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=public static=1 return=0' => [
            1, 1, 0, 1, 0, "/** a comment */\n\tfinal public static function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=public static=1 return=1' => [
            1, 1, 0, 1, 1, "/** a comment */\n\tfinal public static function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=private static=0 return=0' => [
            1, 1, 1, 0, 0, "/** a comment */\n\tfinal private function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=private static=0 return=1' => [
            1, 1, 1, 0, 1, "/** a comment */\n\tfinal private function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=private static=1 return=0' => [
            1, 1, 1, 1, 0, "/** a comment */\n\tfinal private static function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=private static=1 return=1' => [
            1, 1, 1, 1, 1, "/** a comment */\n\tfinal private static function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=protected static=0 return=0' => [
            1, 1, 2, 0, 0, "/** a comment */\n\tfinal protected function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=protected static=0 return=1' => [
            1, 1, 2, 0, 1, "/** a comment */\n\tfinal protected function foo(): int\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=protected static=1 return=0' => [
            1, 1, 2, 1, 0, "/** a comment */\n\tfinal protected static function foo()\n\t{\n\n\t}",
        ];
        yield 'comment=1 final=1 visibility=protected static=1 return=1' => [
            1, 1, 2, 1, 1, "/** a comment */\n\tfinal protected static function foo(): int\n\t{\n\n\t}",
        ];
    }

    #[DataProvider('provideGenerateCases')]
    public function testGenerateCoversEveryPath(
        int $withComment,
        int $isFinal,
        ?int $visibility,
        int $isStatic,
        int $withReturn,
        string $expected
    ): void {
        $method = Method::new('foo');

        if (1 === $withComment) {
            $method->addComment('a comment');
        }

        $method->setFinal(1 === $isFinal);

        if (null !== $visibility) {
            $method->visibility($visibility);
        }

        $method->setStatic(1 === $isStatic);

        if (1 === $withReturn) {
            $method->setReturnType('int');
        }

        $this->assertSame($expected, $method->generate());
    }
}
