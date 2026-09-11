<?php

declare(strict_types=1);

namespace Tests\Container;

use DateTime;
use Omega\Container\Container;
use Omega\Container\ReflectionCache;
use ReflectionClass;
use ReflectionMethod;
use stdClass;

covers(Container::class);
covers(ReflectionCache::class);

beforeEach(function (): void {
    $this->cache = new ReflectionCache();
});

it('gets and caches a reflection class', function (): void {
    $callCount = 0;
    $reflect   = static fn (object $class): ReflectionClass => new ReflectionClass($class);
    $creator   = function () use (&$callCount, $reflect): ReflectionClass {
        $callCount++;

        return $reflect(new stdClass());
    };

    $result1 = $this->cache->getReflectionClass(stdClass::class, $creator);
    $result2 = $this->cache->getReflectionClass(stdClass::class, $creator);

    expect($result1)->toBe($result2);
    expect($callCount)->toBe(1);
});

it('gets and caches a reflection method', function (): void {
    $callCount = 0;
    $creator   = function () use (&$callCount) {
        $callCount++;

        return new ReflectionMethod(DateTime::class, 'getTimestamp');
    };

    $result1 = $this->cache->getReflectionMethod(DateTime::class, 'getTimestamp', $creator);
    $result2 = $this->cache->getReflectionMethod(DateTime::class, 'getTimestamp', $creator);

    expect($result1)->toBe($result2);
    expect($callCount)->toBe(1);
});

it('gets and caches constructor parameters', function (): void {
    $callCount = 0;
    $fixture   = new class {
        public function __construct(
            private int $time = 0,
            private string $name = '',
        ) {
        }

        /**
         * @return array{0: int, 1: string}
         */
        public function signature(): array
        {
            return [$this->time, $this->name];
        }
    };
    $ref         = new ReflectionClass($fixture);
    $constructor = $ref->getConstructor();

    expect($constructor)->not->toBeNull();

    if ($constructor === null) {
        throw new \RuntimeException('Expected the fixture class to have a constructor.');
    }

    $params  = $constructor->getParameters();
    $creator = function () use (&$callCount, $params) {
        $callCount++;

        return $params;
    };

    $result1 = $this->cache->getConstructorParameters($fixture::class, $creator);
    $result2 = $this->cache->getConstructorParameters($fixture::class, $creator);

    expect($result1)->toBe($result2);
    expect($callCount)->toBe(1);
    expect($result1)->toEqual($params);
});

it('clears all caches', function (): void {
    $callCount = 0;
    $reflect   = static fn (object $class): ReflectionClass => new ReflectionClass($class);
    $creator   = function () use (&$callCount, $reflect): ReflectionClass {
        $callCount++;

        return $reflect(new stdClass());
    };

    $this->cache->getReflectionClass(stdClass::class, $creator);

    expect($callCount)->toBe(1);

    $this->cache->clear();

    $this->cache->getReflectionClass(stdClass::class, $creator);

    expect($callCount)->toBe(2);
});