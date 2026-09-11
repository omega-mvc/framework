<?php

declare(strict_types=1);

namespace Tests\Container;

use Closure;
use Omega\Container\Container;
use Omega\Container\Exceptions\AliasException;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use stdClass;
use Tests\Container\Support\AnotherService;
use Tests\Container\Support\ConcreteService;
use Tests\Container\Support\DependencyClass;
use Tests\Container\Support\ServiceInterface;

covers(AliasException::class);
covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(Container::class);
covers(EntryNotFoundException::class);

beforeEach(function (): void {
    $this->container = new Container();
});

it('binds an abstraction to a concrete class', function (): void {
    $this->container->bind(ServiceInterface::class, ConcreteService::class);

    expect($this->container->get(ServiceInterface::class))
        ->toBeInstanceOf(ConcreteService::class);
});

it('binds a closure', function (): void {
    $this->container->bind('foo', fn () => 'bar');

    expect($this->container->get('foo'))->toBe('bar');
});

it('binds a shared singleton', function (): void {
    $this->container->bind('foo', fn () => new stdClass(), true);

    $instance1 = $this->container->get('foo');
    $instance2 = $this->container->get('foo');

    expect($instance1)->toBe($instance2);
});

it('creates a new instance for non-shared bindings', function (): void {
    $this->container->bind('foo', fn () => new stdClass());

    $instance1 = $this->container->make('foo');
    $instance2 = $this->container->make('foo');

    expect($instance1)->not->toBe($instance2);
});

it('overrides a previous binding', function (): void {
    $this->container->bind('foo', fn () => 'bar');
    $this->container->bind('foo', fn () => 'baz');

    expect($this->container->get('foo'))->toBe('baz');
});

it('binds a class name to itself', function (): void {
    $this->container->bind(stdClass::class, stdClass::class);

    expect($this->container->get(stdClass::class))->toBeInstanceOf(stdClass::class);
});

it('uses the abstract as concrete when concrete is null', function (): void {
    $this->container->bind(stdClass::class);

    expect($this->container->get(stdClass::class))->toBeInstanceOf(stdClass::class);
});

it('binds multiple unrelated identifiers', function (): void {
    $this->container->bind('foo', stdClass::class);
    $this->container->bind('bar', AnotherService::class);

    expect($this->container->get('foo'))->toBeInstanceOf(stdClass::class);
    expect($this->container->get('bar'))->toBeInstanceOf(AnotherService::class);
});

it('returns scalar values from bound closures', function (): void {
    $this->container->bind('string_value', fn () => 'hello');
    $this->container->bind('int_value', fn () => 123);

    expect($this->container->get('string_value'))->toBe('hello');
    expect($this->container->get('int_value'))->toBe(123);
});

it('injects dependencies into bound closures', function (): void {
    $this->container->bind('with_param', function (DependencyClass $dep) {
        return $dep;
    });

    expect($this->container->get('with_param'))->toBeInstanceOf(DependencyClass::class);
});

it('respects alias resolution on bindings', function (): void {
    $this->container->alias(ServiceInterface::class, 'my_interface_alias');
    $this->container->bind('my_interface_alias', AnotherService::class);

    $instance = $this->container->get(ServiceInterface::class);

    expect($instance)->toBeInstanceOf(AnotherService::class);
});

it('reports an existing binding through has', function (): void {
    $this->container->bind('foo', stdClass::class);

    expect($this->container->has('foo'))->toBeTrue();
});

it('reports a missing binding through has', function (): void {
    expect($this->container->has('non-existent-binding'))->toBeFalse();
});

it('mirrors has behavior through bound', function (): void {
    $this->container->bind('foo', stdClass::class);

    expect($this->container->bound('foo'))->toBeTrue();
    expect($this->container->bound('non-existent'))->toBeFalse();
});

it('respects alias resolution through bound', function (): void {
    $this->container->bind(ServiceInterface::class, ConcreteService::class);
    $this->container->alias(ServiceInterface::class, 'my_service_alias');

    expect($this->container->bound('my_service_alias'))->toBeTrue();
    expect($this->container->has('my_service_alias'))->toBeTrue();
});

it('returns all current bindings with their metadata', function (): void {
    $this->container->bind('foo', stdClass::class);
    $this->container->bind('bar', ConcreteService::class, true);

    $bindings = $this->container->getBindings();

    expect($bindings)->toHaveKeys(['foo', 'bar']);
    expect($bindings['foo']['shared'])->toBeFalse();
    expect($bindings['bar']['shared'])->toBeTrue();
});

it('updates bindings after an override', function (): void {
    $this->container->bind('foo', stdClass::class);
    $this->container->bind('foo', ConcreteService::class);

    $bindings = $this->container->getBindings();

    expect($bindings)->toHaveKeys(['foo']);
    expect($this->container->get('foo'))->toBeInstanceOf(ConcreteService::class);
});

it('empties bindings after flush', function (): void {
    $this->container->bind('foo', stdClass::class);
    $this->container->flush();

    expect($this->container->getBindings())->toBeEmpty();
});