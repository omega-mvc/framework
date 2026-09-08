<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\Parser\Closure;

use Closure;
use Omega\DocBlockGenerator\Parser\Closure\NamespaceResolver;
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

        if (!$obj instanceof DummyParamClass) {
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
