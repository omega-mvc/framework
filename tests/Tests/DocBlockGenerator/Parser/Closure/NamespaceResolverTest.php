<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\Parser\Closure;

use Closure;
use Omega\DocBlockGenerator\Parser\Closure\NamespaceResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionType;
use Tests\DocBlockGenerator\Fixtures\DummyParamClass;
use Tests\DocBlockGenerator\Fixtures\DummyReturnClass;
use Tests\DocBlockGenerator\Fixtures\DummyStaticClass;
use Tests\DocBlockGenerator\Fixtures\IntersectionAInterface;
use Tests\DocBlockGenerator\Fixtures\IntersectionBInterface;
use Tests\DocBlockGenerator\Fixtures\UnionA;
use Tests\DocBlockGenerator\Fixtures\UnionB;
use Tests\DocBlockGenerator\Fixtures\UnionC;
use Tests\DocBlockGenerator\Fixtures\UnionD;
use Tests\TestCase;

use function array_values;
use function file_put_contents;
use function strtolower;
use function unlink;

#[CoversClass(NamespaceResolver::class)]
final class NamespaceResolverTest extends TestCase
{
    private function closureNamespaceResolverFixture(string $code, string $file): Closure
    {
        file_put_contents($file, $code);
        $fn = require $file;

        /** @var Closure $fn */
        return $fn;
    }

    /**
     * @param array<int, string> $classes
     * @return array<int, string>
     */
    private function collectFromType(?ReflectionType $type, array $classes = []): array
    {
        $resolver = new NamespaceResolver();
        $method   = new ReflectionMethod(NamespaceResolver::class, 'collectFromType');
        $method->invokeArgs($resolver, [$type, &$classes]);

        return $classes;
    }

    public function testCollectsNamespacesFromParametersReturnAndStaticVariables(): void
    {
        $resolver = new NamespaceResolver();

        $fn = static function (
            DummyParamClass $param,
            int $builtin,
        ): DummyReturnClass {
            static $staticObject;

            if (null === $staticObject) {
                $staticObject = new DummyStaticClass();
            }

            return new DummyReturnClass();
        };

        $reflection = new ReflectionFunction($fn);
        $result     = $resolver->resolve($reflection);

        $this->assertContains(DummyParamClass::class, $result);
        $this->assertContains(DummyReturnClass::class, $result);
    }

    public function testIgnoresBuiltinTypes(): void
    {
        $resolver = new NamespaceResolver();

        $fn = static function (int $a, string $b): bool {
            return true;
        };

        $reflection = new ReflectionFunction($fn);
        $result     = $resolver->resolve($reflection);

        $this->assertEmpty($result);
        $this->assertNotContains('int', $result);
        $this->assertNotContains('string', $result);
        $this->assertNotContains('bool', $result);
    }

    public function testCollectsUnionTypes(): void
    {
        $resolver = new NamespaceResolver();

        $code = <<<'PHP'
        <?php
        namespace Tests\DocBlockGenerator\Parser\Closure;

        use Tests\DocBlockGenerator\Fixtures\UnionA;
        use Tests\DocBlockGenerator\Fixtures\UnionB;
        use Tests\DocBlockGenerator\Fixtures\UnionC;
        use Tests\DocBlockGenerator\Fixtures\UnionD;

        return static function (UnionA|UnionB $param): UnionC|UnionD {
            return new UnionC();
        };
        PHP;
        $file = __DIR__ . '/union_closure.php';
        $fn   = $this->closureNamespaceResolverFixture($code, $file);

        $reflection = new ReflectionFunction($fn);
        $result     = $resolver->resolve($reflection);
        unlink($file);

        $this->assertContains(UnionA::class, $result);
        $this->assertContains(UnionB::class, $result);
        $this->assertContains(UnionC::class, $result);
        $this->assertContains(UnionD::class, $result);
    }

    public function testCollectsIntersectionTypes(): void
    {
        $resolver = new NamespaceResolver();

        $code = <<<'PHP'
        <?php
        namespace Tests\DocBlockGenerator\Parser\Closure;

        use Tests\DocBlockGenerator\Fixtures\IntersectionAInterface;
        use Tests\DocBlockGenerator\Fixtures\IntersectionBInterface;

        return static function (
            IntersectionAInterface&IntersectionBInterface $param
        ): IntersectionAInterface&IntersectionBInterface {
            return new class implements IntersectionAInterface, IntersectionBInterface {};
        };
        PHP;
        $file = __DIR__ . '/intersection_closure.php';
        $fn   = $this->closureNamespaceResolverFixture($code, $file);

        $reflection = new ReflectionFunction($fn);
        $result     = $resolver->resolve($reflection);
        unlink($file);

        $this->assertContains(IntersectionAInterface::class, $result);
        $this->assertContains(IntersectionBInterface::class, $result);
    }

    public function testRemovesDuplicatesAndReindexes(): void
    {
        $resolver = new NamespaceResolver();

        $fn = static function (DummyParamClass $a): DummyParamClass {
            static $obj;

            if (!$obj instanceof DummyParamClass) {
                $obj = new DummyParamClass();
            }

            return $obj;
        };

        $reflection = new ReflectionFunction($fn);
        $result     = $resolver->resolve($reflection);

        $this->assertSame([
            DummyParamClass::class,
        ], array_values($result));
    }

    public function testNoParametersNoReturnNoStatics(): void
    {
        $fn = static fn () => null;

        $this->assertSame([], (new NamespaceResolver())->resolve(new ReflectionFunction($fn)));
    }

    public function testStaticScalarsOnly(): void
    {
        $fn = static function (): void {
            static $one = 1;
            static $two = 'x';
        };

        $this->assertSame([], (new NamespaceResolver())->resolve(new ReflectionFunction($fn)));
    }

    public function testStaticObjectsOnly(): void
    {
        $fn = static function (): void {
            static $first = new UnionA();
            static $second = new UnionB();
        };
        $fn();

        $result = (new NamespaceResolver())->resolve(new ReflectionFunction($fn));

        $this->assertContains(UnionA::class, $result);
        $this->assertContains(UnionB::class, $result);
    }

    public function testMixedStaticValues(): void
    {
        $fn = static function (): void {
            static $scalar = 1;
            static $object = new UnionA();
        };
        $fn();

        $result = (new NamespaceResolver())->resolve(new ReflectionFunction($fn));

        $this->assertSame([UnionA::class], $result);
    }

    public function testEvalClosureHasNoFile(): void
    {
        /** @var Closure $fn */
        $fn = eval('return static function (\\Tests\\DocBlockGenerator\\Fixtures\\UnionA $a): void {};');

        $this->assertSame([UnionA::class], (new NamespaceResolver())->resolve(new ReflectionFunction($fn)));
    }

    public function testImportsMatchedAndUnmatched(): void
    {
        $code = <<<'PHP'
        <?php
        namespace Tests\DocBlockGenerator\Parser\Closure;

        use Tests\DocBlockGenerator\Fixtures\UnionA;
        use Tests\DocBlockGenerator\Fixtures\UnionB;
        use Tests\DocBlockGenerator\Fixtures\UnionC;

        return static function (UnionA|UnionB $a): UnionC {
            return new UnionC();
        };
        PHP;
        $file = __DIR__ . '/matched_closure.php';
        $fn   = $this->closureNamespaceResolverFixture($code, $file);

        $result = (new NamespaceResolver())->resolve(new ReflectionFunction($fn));
        unlink($file);

        $this->assertSame([
            UnionA::class,
            UnionB::class,
            UnionC::class,
        ], $result);
    }

    public function testCaseInsensitiveImport(): void
    {
        $code = <<<'PHP'
        <?php
        namespace Tests\DocBlockGenerator\Parser\Closure;

        use tests\docblockgenerator\fixtures\unionA;

        return static function (unionA $a): unionA {
            return $a;
        };
        PHP;
        $file = __DIR__ . '/case_closure.php';
        $fn   = $this->closureNamespaceResolverFixture($code, $file);

        $result = (new NamespaceResolver())->resolve(new ReflectionFunction($fn));
        unlink($file);

        $this->assertCount(1, $result);
        $this->assertSame(strtolower(UnionA::class), strtolower($result[0]));
    }

    public function testSomeNeededMissingFromImports(): void
    {
        $code = <<<'PHP'
        <?php
        namespace Tests\DocBlockGenerator\Parser\Closure;

        use Tests\DocBlockGenerator\Fixtures\UnionA;

        return static function (UnionA $a): \Tests\DocBlockGenerator\Fixtures\UnionB {
            return new UnionB();
        };
        PHP;
        $file = __DIR__ . '/partial_closure.php';
        $fn   = $this->closureNamespaceResolverFixture($code, $file);

        $result = (new NamespaceResolver())->resolve(new ReflectionFunction($fn));
        unlink($file);

        $this->assertSame([
            UnionA::class,
            UnionB::class,
        ], $result);
    }

    public function testDuplicateNeededClasses(): void
    {
        $code = <<<'PHP'
        <?php
        namespace Tests\DocBlockGenerator\Parser\Closure;

        use Tests\DocBlockGenerator\Fixtures\UnionA;

        return static function (UnionA $a): UnionA {
            static $object = new UnionA();

            return $a;
        };
        PHP;
        $file = __DIR__ . '/duplicates_closure.php';
        $fn   = $this->closureNamespaceResolverFixture($code, $file);

        $result = (new NamespaceResolver())->resolve(new ReflectionFunction($fn));
        unlink($file);

        $this->assertSame([UnionA::class], $result);
    }

    public function testOneParameterNoStatics(): void
    {
        $fn = static function (int $a): void {
        };

        $this->assertSame([], (new NamespaceResolver())->resolve(new ReflectionFunction($fn)));
    }

    public function testNoParametersOneScalarStatic(): void
    {
        $fn = static function (): void {
            static $scalar = 1;
        };

        $this->assertSame([], (new NamespaceResolver())->resolve(new ReflectionFunction($fn)));
    }

    public function testOneParameterOneScalarStatic(): void
    {
        $fn = static function (int $a): void {
            static $scalar = 1;
        };

        $this->assertSame([], (new NamespaceResolver())->resolve(new ReflectionFunction($fn)));
    }

    public function testTwoParametersOneScalarStatic(): void
    {
        $fn = static function (int $a, string $b): void {
            static $scalar = 1;
        };

        $this->assertSame([], (new NamespaceResolver())->resolve(new ReflectionFunction($fn)));
    }

    public function testCompoundTypesWithZeroAndOneMembers(): void
    {
        $this->assertSame([], $this->collectFromType(new EmptyUnion()));
        $this->assertSame([UnionA::class], $this->collectFromType(new SingleClassUnion()));
        $this->assertSame([UnionA::class], $this->collectFromType(new EmptyIntersection(), [UnionA::class]));
    }

    public function testPlainAndBuiltinNamedTypes(): void
    {
        $this->assertSame([], $this->collectFromType(new PlainReflectionType()));
        $this->assertSame([], $this->collectFromType(new BuiltinNamedType()));
    }
}
