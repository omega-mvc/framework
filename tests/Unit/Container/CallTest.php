<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Container\Resolver;
use Tests\Container\Support\DependencyClass;
use Tests\Container\Support\DummyStaticClass;
use Tests\Container\Support\InvokableClass;

covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(Container::class);
covers(EntryNotFoundException::class);
covers(Resolver::class);

beforeEach(function (): void {
    $this->container = new Container();
});

it('calls an anonymous function', function (): void {
    $result = $this->container->call(function () {
        return 'called';
    });

    expect($result)->toBe('called');
});

it('calls an object instance method', function (): void {
    $dummy = new class {
        public function foo(): string
        {
            return 'bar';
        }
    };

    expect($this->container->call([$dummy, 'foo']))->toBe('bar');
});

it('calls a static method', function (): void {
    expect($this->container->call([DummyStaticClass::class, 'staticMethod']))
        ->toBe('static called');
});

it('injects dependencies into callables', function (): void {
    $result = $this->container->call(function (DependencyClass $dependency) {
        return $dependency;
    });

    expect($result)->toBeInstanceOf(DependencyClass::class);
});

it('calls a callable with custom parameters', function (): void {
    $result = $this->container->call(function (DependencyClass $dependency, string $name) {
        return [$dependency, $name];
    }, ['name' => 'test']);

    expect($result)->toBeArray();

    if (!is_array($result)) {
        throw new \RuntimeException('Expected an array result.');
    }

    expect($result[0])->toBeInstanceOf(DependencyClass::class);
    expect($result[1])->toBe('test');
});

it('resolves callable dependencies through the container', function (): void {
    $this->container->bind(DependencyClass::class, fn () => new DependencyClass());

    $result = $this->container->call(function (DependencyClass $dependency) {
        return $dependency;
    });

    expect($result)->toBeInstanceOf(DependencyClass::class);
});

it('throws when a callable parameter cannot be resolved', function (): void {
    expect(fn () => $this->container->call(function ($param): void {
    }))
        ->toThrow(
            BindingResolutionException::class,
            'Unable to resolve dependency [Parameter #0 [ <required> $param ]] in callable'
        );
});

it('calls an invokable class', function (): void {
    expect($this->container->call(InvokableClass::class))->toBe('invoked');
});