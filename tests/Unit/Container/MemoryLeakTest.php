<?php

declare(strict_types=1);

namespace Tests\Container;

use Omega\Container\Attribute\Inject;
use Omega\Container\Container;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use ReflectionProperty;
use stdClass;
use Tests\Container\Support\DependencyClass;

use function count;
use function getenv;

covers(BindingResolutionException::class);
covers(CircularAliasException::class);
covers(Container::class);
covers(EntryNotFoundException::class);

beforeEach(function (): void {
    $this->container = new Container();

    if (getenv('OMEGA_TEST_MODE') === 'light') {
        $this->iterations = 10;
    } elseif (getenv('CI') || getenv('GITHUB_ACTIONS')) {
        $this->iterations = 100;
    } else {
        $this->iterations = 100000;
    }
});

it('does not grow metadata when making non-shared instances', function (): void {
    $value = function (string $property): array {
        $reflection = new ReflectionProperty($this->container, $property);
        $reflection->setAccessible(true);
        $internal = $reflection->getValue($this->container);

        if (!is_array($internal)) {
            throw new \RuntimeException("Expected property '{$property}' to be an array.");
        }

        return $internal;
    };

    $initialBindingsCount  = count($value('bindings'));
    $initialInstancesCount = count($value('instances'));
    $initialAliasesCount   = count($value('aliases'));

    for ($i = 0; $i < $this->iterations; $i++) {
        $this->container->make(stdClass::class);
    }

    expect(count($value('bindings')))->toBe($initialBindingsCount);
    expect(count($value('instances')))->toBe($initialInstancesCount);
    expect(count($value('aliases')))->toBe($initialAliasesCount);
})->group('memory-leak');

it('does not leak call metadata under heavy usage', function (): void {
    $callable = function (DependencyClass $dep) {
        return $dep;
    };

    for ($i = 0; $i < $this->iterations; $i++) {
        $this->container->call($callable);
    }

    expect($this->container->call($callable))->toBeInstanceOf(DependencyClass::class);
})->group('memory-leak');

it('does not leak injections under heavy usage', function (): void {
    $injectable = new class {
        public ?DependencyClass $dependency = null;

        #[Inject]
        public function setDependency(DependencyClass $dependency): void
        {
            $this->dependency = $dependency;
        }
    };

    for ($i = 0; $i < $this->iterations; $i++) {
        $this->container->injectOn($injectable);
    }

    expect($injectable->dependency)->toBeInstanceOf(DependencyClass::class);
})->group('memory-leak');