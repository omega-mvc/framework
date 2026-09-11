<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Invoker;
use stdClass;
use Tests\Container\Support\CallableClass;
use Tests\Container\Support\CallableNoDeps;
use Tests\Container\Support\DependencyClass;
use Tests\Container\Support\InvokableInvokeClass;

covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(Container::class);
covers(EntryNotFoundException::class);
covers(Invoker::class);

beforeEach(function (): void {
    $this->container = new Container();
    $this->invoker   = new Invoker($this->container);
});

it('invokes a closure with dependencies', function (): void {
    $result = $this->invoker->call(function (DependencyClass $dep) {
        return $dep;
    });

    expect($result)->toBeInstanceOf(DependencyClass::class);
});

it('invokes a class method with dependencies', function (): void {
    $result = $this->invoker->call([CallableClass::class, 'someMethod']);

    expect($result)->toBeInstanceOf(DependencyClass::class);
});

it('invokes a static class method with dependencies', function (): void {
    $result = $this->invoker->call([CallableClass::class, 'staticMethod']);

    expect($result)->toBeInstanceOf(DependencyClass::class);
});

it('invokes an invokable class', function (): void {
    $result = $this->invoker->call(InvokableInvokeClass::class);

    expect($result)->toBe('invoked');

    $instance = $this->container->get(InvokableInvokeClass::class);

    expect($instance)->toBeInstanceOf(InvokableInvokeClass::class);

    if (!$instance instanceof InvokableInvokeClass) {
        throw new \RuntimeException('Expected an InvokableInvokeClass instance.');
    }

    expect($instance->dep)->not->toBe($instance);
});

it('overrides parameters correctly', function (): void {
    $override = new DependencyClass();

    $result = $this->invoker->call(
        fn (DependencyClass $dep) => $dep,
        ['dep' => $override]
    );

    expect($result)->toBe($override);
});

it('throws for unsupported callable types', function (): void {
    expect(fn () => $this->invoker->call(new stdClass()))
        ->toThrow(BindingResolutionException::class);
});

it('throws when an invokable class has no invoke method', function (): void {
    expect(fn () => $this->invoker->call(CallableNoDeps::class))
        ->toThrow(BindingResolutionException::class);
});