<?php

declare(strict_types=1);

namespace Tests\Template\Parser\Closure;

use Closure;
use Omega\Template\Parser\Closure\NamespaceResolver;
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

covers(NamespaceResolver::class);

function closureNamespaceResolverFixture(string $code, string $file): Closure
{
    file_put_contents($file, $code);
    $fn = require $file;

    /** @var Closure $fn */
    return $fn;
}

it('collects namespaces from parameters return and static variables', function (): void {
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

    expect($result)->toContain(DummyParamClass::class);
    expect($result)->toContain(DummyReturnClass::class);
});

it('ignores builtin types', function (): void {
    $resolver = new NamespaceResolver();

    $fn = static function (int $a, string $b): bool {
        return true;
    };

    $reflection = new ReflectionFunction($fn);
    $result     = $resolver->resolve($reflection);

    expect($result)->toBeEmpty();
    expect($result)->not->toContain('int');
    expect($result)->not->toContain('string');
    expect($result)->not->toContain('bool');
});

it('collects union types', function (): void {
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
    $fn   = closureNamespaceResolverFixture($code, $file);

    $reflection = new ReflectionFunction($fn);
    $result     = $resolver->resolve($reflection);
    unlink($file);

    expect($result)->toContain(UnionA::class);
    expect($result)->toContain(UnionB::class);
    expect($result)->toContain(UnionC::class);
    expect($result)->toContain(UnionD::class);
});

it('collects intersection types', function (): void {
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
    $fn   = closureNamespaceResolverFixture($code, $file);

    $reflection = new ReflectionFunction($fn);
    $result     = $resolver->resolve($reflection);
    unlink($file);

    expect($result)->toContain(IntersectionAInterface::class);
    expect($result)->toContain(IntersectionBInterface::class);
});

it('removes duplicates and reindexes', function (): void {
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

    expect(array_values($result))->toBe([
        DummyParamClass::class,
    ]);
});
