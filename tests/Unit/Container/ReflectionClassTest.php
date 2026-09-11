<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use ReflectionClass;
use ReflectionException;
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

covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(Container::class);
covers(EntryNotFoundException::class);

beforeEach(function (): void {
    $this->container = new Container();
});

it('caches reflection classes', function (): void {
    $reflector1 = $this->container->getReflectionClass(stdClass::class);
    $reflector2 = $this->container->getReflectionClass(stdClass::class);

    expect($reflector1)->toBe($reflector2);
});

it('caches reflection methods', function (): void {
    $reflector1 = $this->container->getReflectionMethod(MyService::class, 'myMethod');
    $reflector2 = $this->container->getReflectionMethod(MyService::class, 'myMethod');

    expect($reflector1)->toBe($reflector2);
});

it('caches parameter resolution', function (): void {
    $params1 = $this->container->getConstructorParameters(Service::class);
    $params2 = $this->container->getConstructorParameters(Service::class);

    expect($params1)->toBeArray();
    expect($params1)->toBe($params2);
});

it('reflects classes with their names', function (): void {
    $reflector = $this->container->getReflectionClass(stdClass::class);

    expect($reflector->getName())->toBe(stdClass::class);
});

it('throws when reflecting a non-existent class', function (): void {
    expect(fn () => $this->container->getReflectionClass('NonExistentClass'))
        ->toThrow(ReflectionException::class, 'Class NonExistentClass does not exist');
});

it('reflects properties', function (): void {
    $reflector = $this->container->getReflectionClass(ClassWithProperties::class);

    expect($reflector->hasProperty('publicProperty'))->toBeTrue();

    $publicProperty = $reflector->getProperty('publicProperty');

    expect($publicProperty->isPublic())->toBeTrue();
    expect($publicProperty->getName())->toBe('publicProperty');

    expect($reflector->hasProperty('protectedProperty'))->toBeTrue();
    expect($reflector->getProperty('protectedProperty')->isPublic())->toBeFalse();

    expect($reflector->hasProperty('privateProperty'))->toBeTrue();
    expect($reflector->getProperty('privateProperty')->isPublic())->toBeFalse();
});

it('reflects methods', function (): void {
    $reflector = $this->container->getReflectionClass(ClassWithMethods::class);

    expect($reflector->hasMethod('publicMethod'))->toBeTrue();

    $publicMethod = $reflector->getMethod('publicMethod');

    expect($publicMethod->isPublic())->toBeTrue();
    expect($publicMethod->getName())->toBe('publicMethod');

    expect($reflector->hasMethod('protectedMethod'))->toBeTrue();
    expect($reflector->getMethod('protectedMethod')->isPublic())->toBeFalse();

    expect($reflector->hasMethod('privateMethod'))->toBeTrue();
    expect($reflector->getMethod('privateMethod')->isPublic())->toBeFalse();
});

it('reflects attributes', function (): void {
    $reflector = $this->container->getReflectionClass(ClassWithAttributes::class);

    expect($reflector->getAttributes(MyClassAttribute::class))->toHaveCount(1);

    $property = $reflector->getProperty('propertyWithAttribute');

    expect($property->getAttributes(MyPropertyAttribute::class))->toHaveCount(1);

    $method = $reflector->getMethod('methodWithAttribute');

    expect($method->getAttributes(MyMethodAttribute::class))->toHaveCount(1);
});

it('reflects inheritance', function (): void {
    $reflector = $this->container->getReflectionClass(ChildClass::class);

    expect($reflector->hasProperty('childProperty'))->toBeTrue();
    expect($reflector->hasMethod('childMethod'))->toBeTrue();
    expect($reflector->hasProperty('parentProperty'))->toBeTrue();
    expect($reflector->hasMethod('parentMethod'))->toBeTrue();

    $parentClassReflector = $reflector->getParentClass();

    expect($parentClassReflector)->not->toBeFalse();

    if ($parentClassReflector === false) {
        throw new \RuntimeException('Expected the class to have a parent class.');
    }

    expect($parentClassReflector->getName())->toBe(ParentClass::class);
});