<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Container;
use Omega\Container\Exceptions\AliasException;
use stdClass;
use Tests\Container\Support\DummyClass;

covers(AliasException::class);
covers(Container::class);

beforeEach(function (): void {
    $this->container = new Container();
});

it('sets an entry through array access', function (): void {
    $this->container['foo'] = 'bar';

    expect(isset($this->container['foo']))->toBeTrue();
});

it('gets an entry through array access', function (): void {
    $this->container['foo'] = 'bar';

    expect($this->container['foo'])->toBe('bar');
});

it('reports whether an entry exists', function (): void {
    $this->container['foo'] = 'bar';

    expect(isset($this->container['foo']))->toBeTrue();
    expect(isset($this->container['baz']))->toBeFalse();
});

it('removes an entry through array access', function (): void {
    $this->container['foo'] = 'bar';
    unset($this->container['foo']);

    expect(isset($this->container['foo']))->toBeFalse();
});

it('creates a new instance on each array read', function (): void {
    $this->container['foo'] = fn () => new stdClass();

    $instance1 = $this->container['foo'];
    $instance2 = $this->container['foo'];

    expect($instance1)->not->toBe($instance2);
});

it('resolves the container instance through array access', function (): void {
    $this->container['std'] = fn () => new stdClass();

    expect($this->container->offsetGet('std'))->toBeInstanceOf(stdClass::class);
});

it('respects aliases through array access', function (): void {
    $this->container->alias(DummyClass::class, 'dummy_alias');
    $this->container['dummy_alias'] = fn () => new DummyClass();

    expect($this->container->offsetGet('dummy_alias'))->toBeInstanceOf(DummyClass::class);
});