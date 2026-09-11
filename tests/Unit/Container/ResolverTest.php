<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Resolver;
use ReflectionProperty;
use RuntimeException;
use stdClass;
use Tests\Container\Support\CircularA;
use Tests\Container\Support\DependencyClass;
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