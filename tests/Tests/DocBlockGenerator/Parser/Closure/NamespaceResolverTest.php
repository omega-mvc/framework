<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\Parser\Closure;

use Closure;
use Omega\DocBlockGenerator\Parser\Closure\NamespaceResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionFunction;
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
use function unlink;

use const PHP_VERSION_ID;

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
}
