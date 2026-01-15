<?php

/**
 * Part of Omega - Tests\Container Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;
use stdClass;
use Tests\Container\Fixtures\Attribute\MyClassAttribute;
use Tests\Container\Fixtures\Attribute\MyMethodAttribute;
use Tests\Container\Fixtures\Attribute\MyPropertyAttribute;
use Tests\Container\Fixtures\ChildClass;
use Tests\Container\Fixtures\ClassWithAttributes;
use Tests\Container\Fixtures\ClassWithMethods;
use Tests\Container\Fixtures\ClassWithProperties;
use Tests\Container\Fixtures\MyService;
use Tests\Container\Fixtures\Service;

/**
 * Class ReflectionClassTest
 *
 * This test class verifies the functionality of reflection handling within the container.
 * It ensures that reflection objects for classes, methods, properties, and constructor
 * parameters are cached correctly, that attributes are supported, and that inheritance
 * is properly reflected. The tests cover public, protected, and private members as well
 * as invalid classes to confirm exceptions are thrown as expected.
 *
 * @category  Tests
 * @package   Container
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(BindingResolutionException::class)]
#[CoversClass(CircularAliasException::class)]
#[CoversClass(EntryNotFoundException::class)]
class ReflectionClassTest extends AbstractTestContainer
{
    /**
     * @return void
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function reflectionCached(): void
    {
        $reflector1 = $this->callProtected('getReflectionClass', [stdClass::class]);
        $reflector2 = $this->callProtected('getReflectionClass', [stdClass::class]);

        $this->assertSame($reflector1, $reflector2);
    }

    /**
     * Test reflection method cached.
     *
     * @return void
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testReflectionMethodCached(): void
    {
        $reflector1 = $this->container->getReflectionMethod(MyService::class, 'myMethod');
        $reflector2 = $this->container->getReflectionMethod(MyService::class, 'myMethod');

        // Assert that the same instance is returned due to caching
        $this->assertSame($reflector1, $reflector2);
    }

    /**
     * Test parameter resolution cached.
     *
     * @return void
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testParameterResolutionCached(): void
    {
        // Trigger resolution for a class with constructor parameters
        $params1 = $this->container->getConstructorParameters(Service::class);
        $params2 = $this->container->getConstructorParameters(Service::class);

        $this->assertIsArray($params1);
        $this->assertContainsOnlyInstancesOf(ReflectionParameter::class, $params1);
        $this->assertSame($params1, $params2);
    }

    /**
     * Test reflection string.
     *
     * @return void
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testReflectionString(): void
    {
        $reflector = $this->callProtected('getReflectionClass', [stdClass::class]);
        $this->assertInstanceOf(ReflectionClass::class, $reflector);
        $this->assertEquals(stdClass::class, $reflector->getName());
    }

    /**
     * Test reflection invalid class.
     *
     * @return void
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testReflectionInvalidClass(): void
    {
        $this->expectException(ReflectionException::class);
        $this->expectExceptionMessage('Class NonExistentClass does not exist');

        $this->callProtected('getReflectionClass', ['NonExistentClass']);
    }

    /**
     * Test reflection properties.
     *
     * @return void
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testReflectionProperties(): void
    {
        $reflector = $this->callProtected('getReflectionClass', [ClassWithProperties::class]);

        $this->assertTrue($reflector->hasProperty('publicProperty'));
        $publicProperty = $reflector->getProperty('publicProperty');
        $this->assertTrue($publicProperty->isPublic());
        $this->assertEquals('publicProperty', $publicProperty->getName());

        // Check that protected/private properties are not directly accessible or reflected as public
        $this->assertTrue($reflector->hasProperty('protectedProperty'));
        $protectedProperty = $reflector->getProperty('protectedProperty');
        $this->assertFalse($protectedProperty->isPublic());

        $this->assertTrue($reflector->hasProperty('privateProperty'));
        $privateProperty = $reflector->getProperty('privateProperty');
        $this->assertFalse($privateProperty->isPublic());
    }

    /**
     * Test reflection methods.
     *
     * @return void
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testReflectionMethods(): void
    {
        $reflector = $this->callProtected('getReflectionClass', [ClassWithMethods::class]);

        $this->assertTrue($reflector->hasMethod('publicMethod'));
        $publicMethod = $reflector->getMethod('publicMethod');
        $this->assertTrue($publicMethod->isPublic());
        $this->assertEquals('publicMethod', $publicMethod->getName());

        // Check that protected/private methods are not directly accessible or reflected as public
        $this->assertTrue($reflector->hasMethod('protectedMethod'));
        $protectedMethod = $reflector->getMethod('protectedMethod');
        $this->assertFalse($protectedMethod->isPublic());

        $this->assertTrue($reflector->hasMethod('privateMethod'));
        $privateMethod = $reflector->getMethod('privateMethod');
        $this->assertFalse($privateMethod->isPublic());
    }

    /**
     * Test reflection supports attributes.
     *
     * @return void
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testReflectionSupportsAttributes(): void
    {
        $reflector = $this->callProtected('getReflectionClass', [ClassWithAttributes::class]);

        // Check class attributes
        $this->assertCount(1, $reflector->getAttributes(MyClassAttribute::class));
        $this->assertNotNull($reflector->getAttributes(MyClassAttribute::class)[0]->newInstance());

        // Check property attributes
        $property = $reflector->getProperty('propertyWithAttribute');
        $this->assertCount(1, $property->getAttributes(MyPropertyAttribute::class));
        $this->assertNotNull($property->getAttributes(MyPropertyAttribute::class)[0]->newInstance());

        // Check method attributes
        $method = $reflector->getMethod('methodWithAttribute');
        $this->assertCount(1, $method->getAttributes(MyMethodAttribute::class));
        $this->assertNotNull($method->getAttributes(MyMethodAttribute::class)[0]->newInstance());
    }

    /**
     * Test reflection inheritance.
     *
     * @return void
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function testReflectionInheritance(): void
    {
        $reflector = $this->callProtected('getReflectionClass', [ChildClass::class]);

        // Check child properties and methods
        $this->assertTrue($reflector->hasProperty('childProperty'));
        $this->assertTrue($reflector->hasMethod('childMethod'));

        // Check parent properties and methods
        $this->assertTrue($reflector->hasProperty('parentProperty'));
        $this->assertTrue($reflector->hasMethod('parentMethod'));

        // Ensure parent class is correctly identified
        $parentClassReflector = $reflector->getParentClass();
        $this->assertNotNull($parentClassReflector);
        $this->assertEquals(Fixtures\ParentClass::class, $parentClassReflector->getName());
    }
}
