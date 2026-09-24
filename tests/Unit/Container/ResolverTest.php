<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Resolver;
use ReflectionFunction;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use Tests\Container\Support\CircularA;
use Tests\Container\Support\DefaultValueConstructorClass;
use Tests\Container\Support\DependencyClass;
use Tests\Container\Support\IntersectionTypeConstructorClass;
use Tests\Container\Support\TypedConstructorClass;

covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(Container::class);
covers(EntryNotFoundException::class);
covers(Resolver::class);

it('resolves a class without a constructor', function (): void {
    $resolver = new Resolver(new Container());

    expect($resolver->resolveClass(stdClass::class))->toBeInstanceOf(stdClass::class);
});

it('resolves a class with dependencies', function (): void {
    $resolver = new Resolver(new Container());

    $instance = $resolver->resolveClass(TypedConstructorClass::class);

    expect($instance)->toBeInstanceOf(TypedConstructorClass::class);

    if (!$instance instanceof TypedConstructorClass) {
        throw new \RuntimeException('Expected a TypedConstructorClass instance.');
    }

    expect($instance->dep)->not->toBe($instance);
});

it('throws on circular dependencies', function (): void {
    $resolver = new Resolver(new Container());

    expect(fn () => $resolver->resolveClass(CircularA::class))
        ->toThrow(BindingResolutionException::class, 'Circular dependency detected');
});

it('resets the parameter override path after a failed make', function (): void {
    $container = new Container();

    expect(function () use ($container): void {
        $container->bind('error', function (): void {
            throw new RuntimeException('Fail');
        });

        $container->make('error');
    })->toThrow(RuntimeException::class);

    $reflection = new ReflectionProperty($container, 'with');
    $reflection->setAccessible(true);

    expect($reflection->getValue($container))->toBeEmpty();
});

it('throws for intersection-typed dependencies', function (): void {
    $resolver = new Resolver(new Container());

    expect(fn () => $resolver->resolveClass(IntersectionTypeConstructorClass::class))
        ->toThrow(
            BindingResolutionException::class,
            'Intersection types are not supported for dependency resolution'
        );
});

it('reports unknown declaring classes for unresolvable function parameters', function (): void {
    $resolver  = new Resolver(new Container());
    $parameter = (new ReflectionFunction('strlen'))->getParameters()[0];

    expect(fn () => $resolver->resolveParameterDependency($parameter))
        ->toThrow(BindingResolutionException::class, 'in class unknown');
});

it('refuses parameters without type hints and available defaults', function (): void {
    $resolver  = new Resolver(new Container());
    $untyped   = function ($value): void {
    };
    $parameter = (new ReflectionFunction($untyped))->getParameters()[0];

    expect(fn () => $resolver->resolveParameterDependency($parameter))
        ->toThrow(BindingResolutionException::class);
});

it('resolves class dependencies from parameter defaults', function (): void {
    $resolver = new Resolver(new Container());

    $instance = $resolver->resolveClass(DefaultValueConstructorClass::class);

    expect($instance)->toBeInstanceOf(DefaultValueConstructorClass::class);

    if (!$instance instanceof DefaultValueConstructorClass) {
        throw new \RuntimeException('Expected a DefaultValueConstructorClass instance.');
    }

    expect($instance->getCount())->toBe(5);
});

it('respects named parameter overrides when resolving a class', function (): void {
    $resolver  = new Resolver(new Container());
    $override  = new DependencyClass();

    $instance = $resolver->resolveClass(TypedConstructorClass::class, ['dep' => $override]);

    expect($instance)->toBeInstanceOf(TypedConstructorClass::class);

    if (!$instance instanceof TypedConstructorClass) {
        throw new \RuntimeException('Expected a TypedConstructorClass instance.');
    }

    expect($instance->dep)->toBe($override);
});

it('respects positional parameter overrides when resolving a class', function (): void {
    $resolver  = new Resolver(new Container());
    $override  = new DependencyClass();

    $instance = $resolver->resolveClass(TypedConstructorClass::class, [0 => $override]);

    expect($instance)->toBeInstanceOf(TypedConstructorClass::class);

    if (!$instance instanceof TypedConstructorClass) {
        throw new \RuntimeException('Expected a TypedConstructorClass instance.');
    }

    expect($instance->dep)->toBe($override);
});

it('applies the active parameter override to a class built inside a factory', function (): void {
    $container = new Container();
    $container->bind(TypedConstructorClass::class, function (Container $container): TypedConstructorClass {
        $instance = $container->build(TypedConstructorClass::class);

        if (!$instance instanceof TypedConstructorClass) {
            throw new RuntimeException('Expected a TypedConstructorClass instance.');
        }

        return $instance;
    });

    $override = new DependencyClass();
    $instance = $container->make(TypedConstructorClass::class, ['dep' => $override]);

    expect($instance)->toBeInstanceOf(TypedConstructorClass::class);

    if (!$instance instanceof TypedConstructorClass) {
        throw new \RuntimeException('Expected a TypedConstructorClass instance.');
    }

    expect($instance->dep)->toBe($override);
});

it('reports unresolvable union-typed dependencies', function (): void {
    $resolver  = new Resolver(new Container());
    $union     = function (DependencyClass|CircularA $value): void {
    };
    $parameter = (new ReflectionFunction($union))->getParameters()[0];

    expect(fn () => $resolver->resolveParameterDependency($parameter))
        ->toThrow(BindingResolutionException::class, 'none of the types in the union are bound in the container');
});

it('resolves a single typed parameter from a bound value', function (): void {
    $container = new Container();
    $container->bind(DependencyClass::class, fn () => new DependencyClass());

    $resolver  = new Resolver($container);
    $single    = function (DependencyClass $value): void {
    };
    $parameter = (new ReflectionFunction($single))->getParameters()[0];

    expect($resolver->resolveParameterDependency($parameter))
        ->toBeInstanceOf(DependencyClass::class);
});
