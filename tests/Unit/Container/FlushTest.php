<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\AliasException;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use ReflectionProperty;
use stdClass;

covers(AliasException::class);
covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(Container::class);
covers(EntryNotFoundException::class);

beforeEach(function (): void {
    $this->container = new Container();
});

it('removes bindings on flush', function (): void {
    $this->container->bind('foo', fn () => 'bar');

    expect($this->container->bound('foo'))->toBeTrue();

    $this->container->flush();

    expect($this->container->bound('foo'))->toBeFalse();
});

it('clears resolved instances on flush', function (): void {
    $this->container->bind('foo', fn () => new stdClass(), true);

    $instance1 = $this->container->get('foo');
    $instance2 = $this->container->get('foo');

    expect($instance1)->toBe($instance2);

    $this->container->flush();
    $this->container->bind('foo', fn () => new stdClass(), true);

    $instance3 = $this->container->get('foo');

    expect($instance1)->not->toBe($instance3);
});

it('clears aliases on flush', function (): void {
    $this->container->bind(stdClass::class);
    $this->container->alias(stdClass::class, 'foo');

    expect($this->container->get('foo'))->toBeInstanceOf(stdClass::class);

    $this->container->flush();

    expect(fn () => $this->container->get('foo'))->toThrow(EntryNotFoundException::class);
});

it('resets all internal state on flush', function (): void {
    $this->container->bind('foo', fn () => new stdClass(), true);
    $this->container->get('foo');
    $this->container->alias('foo', 'bar');

    $this->container->flush();

    $value = function (string $property): mixed {
        $reflection = new ReflectionProperty($this->container, $property);
        $reflection->setAccessible(true);

        return $reflection->getValue($this->container);
    };

    expect($value('bindings'))->toBeEmpty();
    expect($value('instances'))->toBeEmpty();
    expect($value('aliases'))->toBeEmpty();
    expect($value('reflectionCache'))->toBeEmpty();
});

it('resets the reflection cache on clearCache', function (): void {
    $ref1 = $this->container->getReflectionClass(stdClass::class);
    $ref2 = $this->container->getReflectionClass(stdClass::class);

    expect($ref1)->toBe($ref2);

    $this->container->clearCache();

    $ref3 = $this->container->getReflectionClass(stdClass::class);

    expect($ref1)->not->toBe($ref3);
});

it('returns the container from clearCache', function (): void {
    expect($this->container->clearCache())->toBe($this->container);
});