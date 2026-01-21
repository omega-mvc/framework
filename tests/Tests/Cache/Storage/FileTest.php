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

namespace Tests\Cache\Storage;

use Omega\Cache\Exceptions\CachePathException;
use Omega\Cache\Storage\File;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class FileTest
 *
 * Unit tests for the File cache storage implementation.
 *
 * @category   Tests
 * @package    Cache
 * @subpackage Storage
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(File::class)]
class FileTest extends TestCase
{
    /** @var File File storage instance. Used for persistent storage operations on the filesystem. */
    protected File $storage;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     * @throws CachePathException if the cache directory cannot be created or is not writable.
     */
    protected function setUp(): void
    {
        $this->storage = new File(['ttl' => 3600, 'path' => slash(path: __DIR__ . '/cache')]);
    }

    /**
     * Test set and get.
     *
     * @return void
     */
    public function testSetAndGet(): void
    {
        $this->assertTrue($this->storage->set('key1', 'value1'));
        $this->assertEquals('value1', $this->storage->get('key1'));
    }

    /**
     * Test get with default.
     *
     * @return void
     */
    public function testGetWithDefault(): void
    {
        $this->assertEquals('default', $this->storage->get('non_existing_key', 'default'));
    }

    /**
     * Test set with ttl.
     *
     * @return void
     */
    public function testSetWithTtl(): void
    {
        $storage = $this->getMockBuilder(File::class)
            ->setConstructorArgs([['ttl' => 3600, 'path' => slash(path: __DIR__ . '/cache')]])
            ->onlyMethods(['calculateExpirationTimestamp'])
            ->getMock();

        $storage->expects($this->exactly(2))
            ->method('calculateExpirationTimestamp')
            ->willReturnOnConsecutiveCalls(
                time() + 3600,
                time() - 1
            );

        $this->assertTrue($storage->set('key2', 'value2', 3600));
        $this->assertEquals('value2', $storage->get('key2'));

        $storage->set('key2', 'value2', 1);
        $this->assertNull($storage->get('key2'));
    }

    /**
     * Test delete.
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->storage->set('key3', 'value3');
        $this->assertTrue($this->storage->delete('key3'));
        $this->assertFalse($this->storage->has('key3'));
    }

    /**
     * Test delete not existing key.
     *
     * @return void
     */
    public function testDeleteNonExistingKey(): void
    {
        $this->assertFalse($this->storage->delete('non_existing_key'));
    }

    /**
     * Test clear.
     *
     * @return void
     */
    public function testClear(): void
    {
        $this->storage->set('key4', 'value4');
        $this->assertTrue($this->storage->clear());
        $this->assertFalse($this->storage->has('key4'));
    }

    /**
     * Test get multiple.
     *
     * @return void
     */
    public function testGetMultiple(): void
    {
        $this->storage->set('key5', 'value5');
        $this->storage->set('key6', 'value6');
        $result = $this->storage->getMultiple(['key5', 'key6', 'non_existing_key'], 'default');
        $this->assertEquals(['key5' => 'value5', 'key6' => 'value6', 'non_existing_key' => 'default'], $result);
    }

    /**
     * Test set multiple.
     *
     * @return void
     */
    public function testSetMultiple(): void
    {
        $values = ['key7' => 'value7', 'key8' => 'value8'];
        $this->assertTrue($this->storage->setMultiple($values));

        foreach ($values as $key => $value) {
            $this->assertEquals($value, $this->storage->get($key));
        }
    }

    /**
     * Test delete multiple.
     *
     * @return void
     */
    public function testDeleteMultiple(): void
    {
        $this->storage->set('key9', 'value9');
        $this->storage->set('key10', 'value10');
        $this->assertTrue($this->storage->deleteMultiple(['key9', 'key10']));
        $this->assertFalse($this->storage->has('key9'));
        $this->assertFalse($this->storage->has('key10'));
    }

    /**
     * Test has.
     *
     * @return void
     */
    public function testHas(): void
    {
        $this->storage->set('key11', 'value11');
        $this->assertTrue($this->storage->has('key11'));
        $this->assertFalse($this->storage->has('non_existing_key'));
    }

    /**
     * Test increment.
     *
     * @return void
     */
    public function testIncrement(): void
    {
        $this->assertEquals(10, $this->storage->increment('key12', 10));
        $this->assertEquals(20, $this->storage->increment('key12', 10));
    }

    /**
     * Test decrement.
     *
     * @return void
     */
    public function testDecrement(): void
    {
        $this->storage->set('key13', 20);
        $this->assertEquals(10, $this->storage->decrement('key13', 10));
    }

    /**
     * Test get info.
     *
     * @return void
     */
    public function testGetInfo(): void
    {
        $this->storage->set('key14', 'value14');
        $info = $this->storage->getInfo('key14');
        $this->assertArrayHasKey('value', $info);
        $this->assertEquals('value14', $info['value']);
    }

    /**
     * Test remember.
     *
     * @return void
     */
    public function testRemember(): void
    {
        $value = $this->storage->remember('key1', fn (): string => 'value1', 1);
        $this->assertEquals('value1', $value);
    }
}
