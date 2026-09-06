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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheException as PsrCacheExceptionInterface;
use Psr\SimpleCache\InvalidArgumentException as PsrInvalidArgumentExceptionInterface;

/**
 * Class CacheExceptionTest
 *
 * This test suite verifies the behavior of the exception classes provided
 * by the Cache package.
 *
 * @category   Tests
 * @package    Cache
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(CacheConfigurationException::class)]
#[CoversClass(CachePathException::class)]
#[CoversClass(InvalidValueIncrementException::class)]
#[CoversClass(UnknownStorageException::class)]
final class CacheExceptionTest extends TestCase
{
    /**
     * Test CacheConfigurationException without a message.
     *
     * @return void
     */
    public function testCacheConfigurationExceptionWithoutMessage(): void
    {
        $exception = new CacheConfigurationException();

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(PsrInvalidArgumentExceptionInterface::class, $exception);
        $this->assertSame(
            'Invalid cache configuration: a required option is missing or has an invalid value.',
            $exception->getMessage()
        );
    }

    /**
     * Test CacheConfigurationException with a message.
     *
     * @return void
     */
    public function testCacheConfigurationExceptionWithMessage(): void
    {
        $exception = new CacheConfigurationException('custom reason');

        $this->assertSame('Invalid cache configuration: custom reason', $exception->getMessage());
    }

    /**
     * Test InvalidValueIncrementException.
     *
     * @return void
     */
    public function testInvalidValueIncrementException(): void
    {
        $exception = new InvalidValueIncrementException('foo');

        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
        $this->assertInstanceOf(PsrInvalidArgumentExceptionInterface::class, $exception);
        $this->assertSame(
            'The value for the cache key "foo" must be an integer to be incremented.',
            $exception->getMessage()
        );
    }

    /**
     * Test CachePathException.
     *
     * @return void
     */
    public function testCachePathException(): void
    {
        $exception = new CachePathException('/tmp/cache');

        $this->assertInstanceOf(PsrCacheExceptionInterface::class, $exception);
        $this->assertSame(
            'The cache directory "/tmp/cache" could not be created or is not writable. '
            . 'Please ensure the path exists and has proper permissions.',
            $exception->getMessage()
        );
    }

    /**
     * Test UnknownStorageException.
     *
     * @return void
     */
    public function testUnknownStorageException(): void
    {
        $exception = new UnknownStorageException('memcached');

        $this->assertInstanceOf(PsrCacheExceptionInterface::class, $exception);
        $this->assertSame(
            'The cache storage driver "memcached" could not be resolved or is not registered.',
            $exception->getMessage()
        );
    }
}
