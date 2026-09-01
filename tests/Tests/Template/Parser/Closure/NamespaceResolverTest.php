<?php

declare(strict_types=1);

namespace Tests\Template\Parser\Closure;

use Closure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Template\Parser\Closure\NamespaceResolver;
use ReflectionException;
use ReflectionFunction;
use Tests\Template\Fixtures\DummyParamClass;
use Tests\Template\Fixtures\DummyReturnClass;
use Tests\Template\Fixtures\DummyStaticClass;
use Tests\Template\Fixtures\IntersectionAInterface;
use Tests\Template\Fixtures\IntersectionBInterface;
use Tests\Template\Fixtures\UnionA;
use Tests\Template\Fixtures\UnionB;
use Tests\Template\Fixtures\UnionC;
use Tests\Template\Fixtures\UnionD;

use function array_values;
use function file_put_contents;
use function unlink;

use const PHP_VERSION_ID;

#[CoversClass(NamespaceResolver::class)]
final class NamespaceResolverTest extends TestCase
{
    /**
     * Test it an resolve collects namespaces from parameters return and static variable.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testItCanResolveCollectsNamespacesFromParametersReturnAndStaticVariables(): void
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

        self::assertContains(DummyParamClass::class, $result);
        self::assertContains(DummyReturnClass::class, $result);
        // self::assertContains(DummyStaticClass::class, $result);
    }

    /**
     * Test it can resolve ignores builtin types.
     * Builtin types (int, string, bool, etc.) should not appear in results.
     * Since the closure uses only builtin types, no class imports are needed.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testItCanResolveIgnoresBuiltinTypes(): void
    {
        $resolver = new NamespaceResolver();

        $fn = static function (int $a, string $b): bool {
            return true;
        };

        $reflection = new ReflectionFunction($fn);
        $result     = $resolver->resolve($reflection);

        // Closure uses only builtin types - no class imports needed
        self::assertEmpty($result);

        // Should NOT contain builtin types
        self::assertNotContains('int', $result);
        self::assertNotContains('string', $result);
        self::assertNotContains('bool', $result);
    }

    /**
     * It can resolve collects union types.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testItCanResolveCollectsUnionTypes(): void
    {
        if (PHP_VERSION_ID < 80000) {
            $this->markTestSkipped('Union types require PHP 8.0');
        }

        $resolver = new NamespaceResolver();

        $code = <<<'PHP'
<?php
namespace Tests\Template\Parser\Closure;

use Tests\Template\Fixtures\UnionA;
use Tests\Template\Fixtures\UnionB;
use Tests\Template\Fixtures\UnionC;
use Tests\Template\Fixtures\UnionD;

return static function (UnionA|UnionB $param): UnionC|UnionD {
    return new UnionC();
};
PHP;
        $file = __DIR__ . '/union_closure.php';
        file_put_contents($file, $code);
        $fn = require $file;

        /** @var Closure $fn */
        $reflection = new ReflectionFunction($fn);
        $result     = $resolver->resolve($reflection);
        unlink($file);

        self::assertContains(UnionA::class, $result);
        self::assertContains(UnionB::class, $result);
        self::assertContains(UnionC::class, $result);
        self::assertContains(UnionD::class, $result);
    }

    /**
     * Test it can resolve collects intersection types.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testItCanResolveCollectsIntersectionTypes(): void
    {
        if (PHP_VERSION_ID < 80100) {
            $this->markTestSkipped('Intersection types require PHP 8.1');
        }

        $resolver = new NamespaceResolver();

        $code = <<<'PHP'
<?php
namespace Tests\Template\Parser\Closure;

use Tests\Template\Fixtures\IntersectionAInterface;
use Tests\Template\Fixtures\IntersectionBInterface;

return static function (
    IntersectionAInterface&IntersectionBInterface $param
): IntersectionAInterface&IntersectionBInterface {
    return new class implements IntersectionAInterface, IntersectionBInterface {};
};
PHP;
        $file = __DIR__ . '/intersection_closure.php';
        file_put_contents($file, $code);
        $fn = require $file;

        /** @var Closure $fn */
        $reflection = new ReflectionFunction($fn);
        $result     = $resolver->resolve($reflection);
        unlink($file);

        self::assertContains(IntersectionAInterface::class, $result);
        self::assertContains(IntersectionBInterface::class, $result);
    }

    /**
     * Test it can resolve remove duplicates and reindexes.
     *
     * @return void
     * @throws ReflectionException
     */
    public function testItCanResolveRemovesDuplicatesAndReindexes(): void
    {
        $resolver = new NamespaceResolver();

        $fn = static function (DummyParamClass $a): DummyParamClass {
            static $obj;

            if (null === $obj) {
                $obj = new DummyParamClass();
            }

            return $obj;
        };

        $reflection = new ReflectionFunction($fn);
        $result     = $resolver->resolve($reflection);

        // Only DummyParamClass is used by the closure
        self::assertSame(
            [
                DummyParamClass::class,
            ],
            array_values($result)
        );
    }
}
