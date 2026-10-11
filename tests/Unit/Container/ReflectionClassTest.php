<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionException;
use RuntimeException;
use stdClass;
use Tests\Container\Support\Attribute\MyClassAttribute;
use Tests\Container\Support\Attribute\MyMethodAttribute;
use Tests\Container\Support\Attribute\MyPropertyAttribute;
use Tests\Container\Support\ChildClass;
use Tests\Container\Support\ClassWithAttributes;
use Tests\Container\Support\ClassWithMethods;
use Tests\Container\Support\ClassWithProperties;
use Tests\Container\Support\MyService;
use Tests\Container\Support\ParentClass;
use Tests\Container\Support\Service;
use Tests\TestCase;

#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(Container::class)]
#[CoversClass(EntryNotFoundException::class)]
final class ReflectionClassTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testCachesReflectionClasses(): void
    {
        $reflector1 = $this->container->getReflectionClass(stdClass::class);
        $reflector2 = $this->container->getReflectionClass(stdClass::class);

        $this->assertSame($reflector1, $reflector2);
    }

    public function testCachesReflectionMethods(): void
    {
        $reflector1 = $this->container->getReflectionMethod(MyService::class, 'myMethod');
        $reflector2 = $this->container->getReflectionMethod(MyService::class, 'myMethod');

        $this->assertSame($reflector1, $reflector2);
    }

    public function testCachesParameterResolution(): void
    {
        $params1 = $this->container->getConstructorParameters(Service::class);
        $params2 = $this->container->getConstructorParameters(Service::class);

        $this->assertIsArray($params1);
        $this->assertSame($params1, $params2);
    }

    public function testReflectsClassesWithTheirNames(): void
    {
        $reflector = $this->container->getReflectionClass(stdClass::class);

        $this->assertSame(stdClass::class, $reflector->getName());
    }

    public function testThrowsWhenReflectingANonExistentClass(): void
    {
        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessage('Class NonExistentClass does not exist');

        $this->container->getReflectionClass('NonExistentClass');
    }

    public function testReflectsProperties(): void
    {
        $reflector = $this->container->getReflectionClass(ClassWithProperties::class);

        $this->assertTrue($reflector->hasProperty('publicProperty'));

        $publicProperty = $reflector->getProperty('publicProperty');

        $this->assertTrue($publicProperty->isPublic());
        $this->assertSame('publicProperty', $publicProperty->getName());

        $this->assertTrue($reflector->hasProperty('protectedProperty'));
        $this->assertFalse($reflector->getProperty('protectedProperty')->isPublic());

        $this->assertTrue($reflector->hasProperty('privateProperty'));
        $this->assertFalse($reflector->getProperty('privateProperty')->isPublic());
    }

    public function testReflectsMethods(): void
    {
        $reflector = $this->container->getReflectionClass(ClassWithMethods::class);

        $this->assertTrue($reflector->hasMethod('publicMethod'));

        $publicMethod = $reflector->getMethod('publicMethod');

        $this->assertTrue($publicMethod->isPublic());
        $this->assertSame('publicMethod', $publicMethod->getName());

        $this->assertTrue($reflector->hasMethod('protectedMethod'));
        $this->assertFalse($reflector->getMethod('protectedMethod')->isPublic());

        $this->assertTrue($reflector->hasMethod('privateMethod'));
        $this->assertFalse($reflector->getMethod('privateMethod')->isPublic());
    }

    public function testReflectsAttributes(): void
    {
        $reflector = $this->container->getReflectionClass(ClassWithAttributes::class);

        $this->assertCount(1, $reflector->getAttributes(MyClassAttribute::class));

        $property = $reflector->getProperty('propertyWithAttribute');

        $this->assertCount(1, $property->getAttributes(MyPropertyAttribute::class));

        $method = $reflector->getMethod('methodWithAttribute');

        $this->assertCount(1, $method->getAttributes(MyMethodAttribute::class));
    }

    public function testReflectsInheritance(): void
    {
        $reflector = $this->container->getReflectionClass(ChildClass::class);

        $this->assertTrue($reflector->hasProperty('childProperty'));
        $this->assertTrue($reflector->hasMethod('childMethod'));
        $this->assertTrue($reflector->hasProperty('parentProperty'));
        $this->assertTrue($reflector->hasMethod('parentMethod'));

        $parentClassReflector = $reflector->getParentClass();

        $this->assertNotFalse($parentClassReflector);

        $this->assertSame(ParentClass::class, $parentClassReflector->getName());
    }
}
