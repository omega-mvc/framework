<?php

declare(strict_types=1);

namespace Tests\Cache\Exceptions;

use InvalidArgumentException;
use Omega\Cache\Exceptions\CacheConfigurationException;
use Omega\Cache\Exceptions\CachePathException;
use Omega\Cache\Exceptions\InvalidValueIncrementException;
use Omega\Cache\Exceptions\UnknownStorageException;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\SimpleCache\CacheException as PsrCacheExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentExceptionInterface;
use Tests\TestCase;

#[CoversClass(CacheConfigurationException::class)]
#[CoversClass(CachePathException::class)]
#[CoversClass(InvalidValueIncrementException::class)]
#[CoversClass(UnknownStorageException::class)]
final class CacheExceptionTest extends TestCase
{
    public function testReportsTheDefaultMessageWhenNoReasonIsGiven(): void
    {
        $exception = new CacheConfigurationException();

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(PsrInvalidArgumentExceptionInterface::class, $exception);
        $this->assertSame(
            'Invalid cache configuration: a required option is missing or has an invalid value.',
            $exception->getMessage()
        );
    }

    public function testPrependsTheReasonToTheConfigurationExceptionMessage(): void
    {
        $exception = new CacheConfigurationException('custom reason');

        $this->assertSame('Invalid cache configuration: custom reason', $exception->getMessage());
    }

    public function testReportsTheCacheKeyInTheInvalidIncrementMessage(): void
    {
        $exception = new InvalidValueIncrementException('foo');

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(PsrInvalidArgumentExceptionInterface::class, $exception);
        $this->assertSame(
            'The value for the cache key "foo" must be an integer to be incremented.',
            $exception->getMessage()
        );
    }

    public function testReportsTheCacheDirectoryThatCouldNotBeCreated(): void
    {
        $exception = new CachePathException('/tmp/cache');

        $this->assertInstanceOf(PsrCacheExceptionInterface::class, $exception);
        $this->assertSame(
            'The cache directory "/tmp/cache" could not be created or is not writable. '
            . 'Please ensure the path exists and has proper permissions.',
            $exception->getMessage()
        );
    }

    public function testReportsTheStorageDriverThatCouldNotBeResolved(): void
    {
        $exception = new UnknownStorageException('memcached');

        $this->assertInstanceOf(PsrCacheExceptionInterface::class, $exception);
        $this->assertSame(
            'The cache storage driver "memcached" could not be resolved or is not registered.',
            $exception->getMessage()
        );
    }
}
