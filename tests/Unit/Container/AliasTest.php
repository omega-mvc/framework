<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\AliasException;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Tests\Container\Support\DummyClass;

covers(AliasException::class);
covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(Container::class);
covers(EntryNotFoundException::class);

beforeEach(function (): void {
    $this->container = new Container();
});

it('resolves a basic alias', function (): void {
    $this->container->bind('foo', fn () => 'foo');
    $this->container->alias('foo', 'foo-alias');

    expect($this->container->get('foo-alias'))->toBe('foo');
});

it('resolves alias chains recursively', function (): void {
    $this->container->bind('foo', fn () => 'bar');
    $this->container->alias('foo', 'alias1');
    $this->container->alias('alias1', 'alias2');

    expect($this->container->get('alias2'))->toBe('bar');
});

it('lets the latest alias definition shadow previous ones', function (): void {
    $this->container->bind('foo', fn () => 'foo-instance');
    $this->container->bind('bar', fn () => 'bar-instance');
    $this->container->alias('foo', 'shadow');
    $this->container->alias('bar', 'shadow');

    expect($this->container->get('shadow'))->toBe('bar-instance');
});

it('returns the resolved abstract for an alias', function (): void {
    $this->container->alias('foo', 'bar');

    expect($this->container->getAlias('bar'))->toBe('foo');
});

it('resolves a binding through an alias used as abstract', function (): void {
    $this->container->alias('foo', 'bar');
    $this->container->bind('bar', fn () => 'baz');

    expect($this->container->get('foo'))->toBe('baz');
});

it('throws when an alias maps to itself', function (): void {
    expect(fn () => $this->container->alias('foo', 'foo'))
        ->toThrow(AliasException::class);
});

it('throws when an alias chain is circular', function (): void {
    $this->container->alias('foo', 'bar');
    $this->container->alias('bar', 'foo');

    expect(fn () => $this->container->get('foo'))
        ->toThrow(CircularAliasException::class);
});

it('shares the resolved instance between a binding and its alias', function (): void {
    $this->container->bind(DummyClass::class, null, true);
    $this->container->alias(DummyClass::class, 'dummy_alias');

    $instance1 = $this->container->get(DummyClass::class);
    $instance2 = $this->container->get('dummy_alias');

    expect($instance1)->toBe($instance2);
});