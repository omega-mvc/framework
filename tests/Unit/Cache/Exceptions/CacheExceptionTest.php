<?php

/**
 * Part of Omega - Tests\Cache Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Cache\Exceptions;

use InvalidArgumentException;
use Omega\Cache\Exceptions\CacheConfigurationException;
use Omega\Cache\Exceptions\CachePathException;
use Omega\Cache\Exceptions\InvalidValueIncrementException;
use Omega\Cache\Exceptions\UnknownStorageException;
use Psr\SimpleCache\CacheException as PsrCacheExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentExceptionInterface;

covers(
    CacheConfigurationException::class,
    CachePathException::class,
    InvalidValueIncrementException::class,
    UnknownStorageException::class,
);

it('reports the default message when no reason is given', function (): void {
    $exception = new CacheConfigurationException();

    $this->assertInstanceOf(InvalidArgumentException::class, $exception);
    $this->assertInstanceOf(PsrInvalidArgumentExceptionInterface::class, $exception);
    expect($exception->getMessage())->toBe(
        'Invalid cache configuration: a required option is missing or has an invalid value.'
    );
});

it('prepends the reason to the configuration exception message', function (): void {
    $exception = new CacheConfigurationException('custom reason');

    expect($exception->getMessage())->toBe('Invalid cache configuration: custom reason');
});

it('reports the cache key in the invalid increment message', function (): void {
    $exception = new InvalidValueIncrementException('foo');

    $this->assertInstanceOf(InvalidArgumentException::class, $exception);
    $this->assertInstanceOf(PsrInvalidArgumentExceptionInterface::class, $exception);
    expect($exception->getMessage())->toBe(
        'The value for the cache key "foo" must be an integer to be incremented.'
    );
});

it('reports the cache directory that could not be created', function (): void {
    $exception = new CachePathException('/tmp/cache');

    $this->assertInstanceOf(PsrCacheExceptionInterface::class, $exception);
    expect($exception->getMessage())->toBe(
        'The cache directory "/tmp/cache" could not be created or is not writable. '
        . 'Please ensure the path exists and has proper permissions.'
    );
});

it('reports the storage driver that could not be resolved', function (): void {
    $exception = new UnknownStorageException('memcached');

    $this->assertInstanceOf(PsrCacheExceptionInterface::class, $exception);
    expect($exception->getMessage())->toBe(
        'The cache storage driver "memcached" could not be resolved or is not registered.'
    );
});