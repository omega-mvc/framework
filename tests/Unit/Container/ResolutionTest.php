<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\AliasException;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use stdClass;
use Tests\Container\Support\DeepA;
use Tests\Container\Support\DeepB;
use Tests\Container\Support\DeepC;
use Tests\Container\Support\DependencyClass;
use Tests\Container\Support\PrivateConstructorClass;
use Tests\Container\Support\ScalarConstructorClass;
use Tests\Container\Support\Service;
use Tests\Container\Support\UnresolvableClass;

covers(AliasException::class);
covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(Container::class);
covers(EntryNotFoundException::class);

beforeEach(function (): void {
    $this->container = new Container();
});

it('returns a shared instance through get', function (): void {
    $this->container->bind(DependencyClass::class, null, true);

    $instance1 = $this->container->get(DependencyClass::class);
    $instance2 = $this->container->get(DependencyClass::class);

    expect($instance1)->toBe($instance2);
});

it('throws when the requested entry is not found', function (): void {
    expect(fn () => $this->container->get('non-existent-class'))
        ->toThrow(EntryNotFoundException::class);
});

it('creates a fresh instance through make', function (): void {
    $instance1 = $this->container->make(stdClass::class);
    $instance2 = $this->container->make(stdClass::class);

    expect($instance1)->not->toBe($instance2);
});

it('makes instances with parameters', function (): void {
    $instance = $this->container->make(Service::class, ['value' => 'custom']);

    expect($instance)->toBeInstanceOf(Service::class);

    if (!$instance instanceof Service) {
        throw new \RuntimeException('Expected a Service instance.');
    }

    expect($instance->value)->toBe('custom');
});

it('resolves closures through get', function (): void {
    $this->container->bind('test-closure', function ($container) {
        return 'resolved from closure';
    });

    expect($this->container->get('test-closure'))->toBe('resolved from closure');
});

it('resolves closures through make', function (): void {
    $this->container->bind('test-closure', function ($container) {
        return 'resolved from closure';
    });

    expect($this->container->make('test-closure'))->toBe('resolved from closure');
});

it('resolves through an alias with get', function (): void {
    $this->container->bind('dependency', DependencyClass::class);
    $this->container->alias('dependency', 'alias');

    expect($this->container->get('alias'))->toBeInstanceOf(DependencyClass::class);
});

it('resolves through an alias with make', function (): void {
    $this->container->bind(DependencyClass::class);
    $this->container->alias(DependencyClass::class, 'dependency_alias');

    expect($this->container->make('dependency_alias'))->toBeInstanceOf(DependencyClass::class);
});

it('caches singleton instances', function (): void {
    $counter = 0;

    $this->container->bind(DependencyClass::class, function () use (&$counter) {
        $counter++;

        return new DependencyClass();
    }, true);

    $this->container->get(DependencyClass::class);
    $this->container->get(DependencyClass::class);

    expect($counter)->toBe(1);
});

it('resolves recursive dependencies through get', function (): void {
    $instance = $this->container->get(DeepA::class);

    expect($instance)->toBeInstanceOf(DeepA::class);

    if (!$instance instanceof DeepA) {
        throw new \RuntimeException('Expected a DeepA instance.');
    }

    expect($instance->b)->not->toBe($instance);
    expect($instance->b->c)->not->toBe($instance->b);
});

it('resolves recursive dependencies through make', function (): void {
    $instance = $this->container->make(DeepA::class);

    expect($instance)->toBeInstanceOf(DeepA::class);

    if (!$instance instanceof DeepA) {
        throw new \RuntimeException('Expected a DeepA instance.');
    }

    expect($instance->b)->not->toBe($instance);
    expect($instance->b->c)->not->toBe($instance->b);
});

it('throws when a dependency cannot be resolved', function (): void {
    expect(fn () => $this->container->make(UnresolvableClass::class))
        ->toThrow(BindingResolutionException::class);
});

it('reports an unbound instantiable class through has', function (): void {
    expect($this->container->has(stdClass::class))->toBeTrue();
});

it('reports a class without a public constructor as not instantiable', function (): void {
    expect($this->container->has(PrivateConstructorClass::class))->toBeFalse();
});

it('reports a resolved shared abstract through resolved', function (): void {
    $this->container->bind(DependencyClass::class, null, true);

    expect($this->container->resolved(DependencyClass::class))->toBeFalse();

    $this->container->get(DependencyClass::class);

    expect($this->container->resolved(DependencyClass::class))->toBeTrue();
});

it('returns true from bound when only a cached instance exists', function (): void {
    // get() caches the resolved object in the instances map without ever
    // registering a binding entry, so bound() must pick it up from there.
    $this->container->get(stdClass::class);

    expect($this->container->bound(stdClass::class))->toBeTrue();
});

it('returns an empty array as the default parameter override', function (): void {
    expect($this->container->getLastParameterOverride())->toBe([]);
});

it('applies the active parameter override when building inside a factory', function (): void {
    $this->container->bind('service-factory', function (Container $container) {
        return $container->build(Service::class);
    });

    $instance = $this->container->make('service-factory', ['value' => 'from-override']);

    expect($instance)->toBeInstanceOf(Service::class);

    if (!$instance instanceof Service) {
        throw new \RuntimeException('Expected a Service instance.');
    }

    expect($instance->value)->toBe('from-override');
});

it('resolves positional constructor parameters', function (): void {
    $instance = $this->container->make(ScalarConstructorClass::class, [0 => 'positional']);

    expect($instance)->toBeInstanceOf(ScalarConstructorClass::class);

    if (!$instance instanceof ScalarConstructorClass) {
        throw new \RuntimeException('Expected a ScalarConstructorClass instance.');
    }

    expect($instance->getName())->toBe('positional');
});