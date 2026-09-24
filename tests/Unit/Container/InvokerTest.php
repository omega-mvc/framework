<?php

declare(strict_types=1);

namespace Tests\Container;

use Closure;
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

it('invokes a method on an already instantiated object', function (): void {
    $instance = new CallableClass();

    $result = $this->invoker->call([$instance, 'someMethod']);

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

it('invokes an invokable object instance', function (): void {
    $expected = new DependencyClass();
    $this->container->bind(DependencyClass::class, static fn () => $expected, true);

    $invokable = new InvokableInvokeClass();

    $result = $this->invoker->call($invokable);

    expect($result)->toBe('invoked');
    expect($invokable->dep)->toBe($expected);
});

it('injects the container into self-typed container parameters', function (): void {
    $subclass = new class extends Container {
        public function makeClosure(): Closure
        {
            return fn (self $container) => $container;
        }
    };

    $invoker = new Invoker($subclass);

    expect($invoker->call($subclass->makeClosure()))->toBe($subclass);
});

it('falls back to parameter defaults', function (): void {
    $result = $this->invoker->call(fn ($name = 'default') => $name);

    expect($result)->toBe('default');
});

it('consumes leftover parameters positionally', function (): void {
    $result = $this->invoker->call(fn (int $value) => 'value:' . $value, ['unmatched' => 7]);

    expect($result)->toBe('value:7');
});

it('appends unmatched parameters to the invocation arguments', function (): void {
    $result = $this->invoker->call(fn ($first) => $first, ['first' => 1, 'second' => 2]);

    expect($result)->toBe(1);
});

it('overrides parameters by position', function (): void {
    $override = new DependencyClass();

    $result = $this->invoker->call(
        fn (DependencyClass $dep) => $dep,
        [0 => $override]
    );

    expect($result)->toBe($override);
});

it('throws when a resolved callable class is not an object', function (): void {
    $this->container->set('answer', 42);

    expect(fn () => $this->invoker->call(['answer', 'anyMethod']))
        ->toThrow(BindingResolutionException::class, 'Resolved class answer is not an object instance.');
});

it('throws when a string callable is neither a class nor a function', function (): void {
    expect(fn () => $this->invoker->call('undeclared_function_name'))
        ->toThrow(BindingResolutionException::class, 'Unable to call the given callable. Unsupported type.');
});

it('invokes a named global function with dependencies', function (): void {
    $result = $this->invoker->call('Tests\Container\invokerTestFunction');

    expect($result)->toBeInstanceOf(DependencyClass::class);
});

it('injects the container into an untyped container parameter', function (): void {
    $result = $this->invoker->call(fn ($container) => $container);

    expect($result)->toBe($this->container);
});

it('resolves a container-named parameter with a class type', function (): void {
    $result = $this->invoker->call(fn (DependencyClass $container) => $container);

    expect($result)->toBeInstanceOf(DependencyClass::class);
});

it('falls back to the default for a container-named built-in parameter', function (): void {
    $result = $this->invoker->call(fn (string $container = 'default-string') => $container);

    expect($result)->toBe('default-string');
});

it('throws when a parameter has no resolvable value', function (): void {
    expect(fn () => $this->invoker->call(fn ($missing) => $missing))
        ->toThrow(BindingResolutionException::class);
});

it('falls back to the default for a built-in typed parameter', function (): void {
    $result = $this->invoker->call(fn (int $num = 3) => $num);

    expect($result)->toBe(3);
});

it('consumes leftover parameters for an untyped parameter', function (): void {
    $result = $this->invoker->call(fn ($value) => $value, ['unmatched' => 7]);

    expect($result)->toBe(7);
});

it('falls back to the default for a container-named union-typed parameter', function (): void {
    $result = $this->invoker->call(fn (DependencyClass|stdClass|null $container = null) => $container);

    expect($result)->toBeNull();
});

/**
 * Named function used by the invoker function test above to prove that a
 * plain function-name string resolves its dependencies and is invoked.
 *
 * @param DependencyClass $dependency The injected dependency
 * @return DependencyClass The very same dependency passed through
 */
function invokerTestFunction(DependencyClass $dependency): DependencyClass
{
    return $dependency;
}
