<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Resolver;
use stdClass;
use Tests\Container\Support\CircularA;
use Tests\Container\Support\ClassWithMissingDependency;
use Tests\Container\Support\ClassWithNullableUnionTypeConstructor;
use Tests\Container\Support\ClassWithUnionTypeConstructor;
use Tests\Container\Support\Dependant;
use Tests\Container\Support\Dependency;
use Tests\Container\Support\DependencyClass;
use Tests\Container\Support\PrivateConstructorClass;
use Tests\Container\Support\ScalarConstructorClass;
use Tests\Container\Support\Service;
use Tests\Container\Support\TypedConstructorClass;
use Tests\Container\Support\UnionDependencyOne;
use Tests\Container\Support\UnionDependencyTwo;

covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(Container::class);
covers(EntryNotFoundException::class);
covers(Resolver::class);

beforeEach(function (): void {
    $this->container = new Container();
});

it('builds a class without dependencies', function (): void {
    expect($this->container->build(stdClass::class))->toBeInstanceOf(stdClass::class);
});

it('builds a class with dependencies', function (): void {
    $instance = $this->container->build(Dependant::class);

    expect($instance)->toBeInstanceOf(Dependant::class);

    if (!$instance instanceof Dependant) {
        throw new \RuntimeException('Expected a Dependant instance.');
    }

    expect($instance->dep)->not->toBe($instance);
});

it('builds a class with custom parameters', function (): void {
    $instance = $this->container->build(Service::class, ['value' => 'custom']);

    expect($instance)->toBeInstanceOf(Service::class);

    if (!$instance instanceof Service) {
        throw new \RuntimeException('Expected a Service instance.');
    }

    expect($instance->value)->toBe('custom');
});

it('builds from a closure', function (): void {
    expect($this->container->build(fn () => 'foo'))->toBe('foo');
});

it('throws when a dependency is missing', function (): void {
    expect(fn () => $this->container->build(ClassWithMissingDependency::class))
        ->toThrow(BindingResolutionException::class);
});

it('throws on circular dependencies', function (): void {
    expect(fn () => $this->container->build(CircularA::class))
        ->toThrow(BindingResolutionException::class);
});

it('builds classes with typed constructors', function (): void {
    $instance = $this->container->build(TypedConstructorClass::class);

    expect($instance)->toBeInstanceOf(TypedConstructorClass::class);

    if (!$instance instanceof TypedConstructorClass) {
        throw new \RuntimeException('Expected a TypedConstructorClass instance.');
    }

    expect($instance->dep)->not->toBe($instance);
});

it('resolves the first bound union type', function (): void {
    $this->container->bind(UnionDependencyOne::class, fn () => new UnionDependencyOne());
    $instance = $this->container->build(ClassWithUnionTypeConstructor::class);

    expect($instance)->toBeInstanceOf(ClassWithUnionTypeConstructor::class);

    if (!$instance instanceof ClassWithUnionTypeConstructor) {
        throw new \RuntimeException('Expected a ClassWithUnionTypeConstructor instance.');
    }

    expect($instance->dependency)->toBeInstanceOf(UnionDependencyOne::class);
});

it('resolves the second bound union type', function (): void {
    $this->container->bind(UnionDependencyTwo::class, fn () => new UnionDependencyTwo());
    $instance = $this->container->build(ClassWithUnionTypeConstructor::class);

    expect($instance)->toBeInstanceOf(ClassWithUnionTypeConstructor::class);

    if (!$instance instanceof ClassWithUnionTypeConstructor) {
        throw new \RuntimeException('Expected a ClassWithUnionTypeConstructor instance.');
    }

    expect($instance->dependency)->toBeInstanceOf(UnionDependencyTwo::class);
});

it('throws when no union type is bound', function (): void {
    expect(fn () => $this->container->build(ClassWithUnionTypeConstructor::class))
        ->toThrow(BindingResolutionException::class);
});

it('resolves nullable union type constructors to null', function (): void {
    $instance = $this->container->build(ClassWithNullableUnionTypeConstructor::class);

    expect($instance)->toBeInstanceOf(ClassWithNullableUnionTypeConstructor::class);

    if (!$instance instanceof ClassWithNullableUnionTypeConstructor) {
        throw new \RuntimeException('Expected a ClassWithNullableUnionTypeConstructor instance.');
    }

    expect($instance->dependency)->toBeNull();
});

it('throws for scalar constructor parameters', function (): void {
    expect(fn () => $this->container->build(ScalarConstructorClass::class))
        ->toThrow(BindingResolutionException::class);
});

it('throws for private constructors', function (): void {
    expect(fn () => $this->container->build(PrivateConstructorClass::class))
        ->toThrow(BindingResolutionException::class);
});