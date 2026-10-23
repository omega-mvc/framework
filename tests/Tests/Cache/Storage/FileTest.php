<?php

declare(strict_types=1);

namespace Tests\Cache\Storage;

use DateInterval;
use DateTimeImmutable;
use Omega\Cache\Exceptions\CacheConfigurationException;
use Omega\Cache\Exceptions\CachePathException;
use Omega\Cache\Exceptions\InvalidValueIncrementException;
use Omega\Cache\Storage\FileStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(FileStorage::class)]
final class FileTest extends TestCase
{
    private FileStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new FileStorage(['ttl' => 3600, 'path' => __DIR__ . '/../fixtures/cache']);
    }

    public function testSetsAndGetsAValue(): void
    {
        $this->assertTrue($this->storage->set('key1', 'value1'));
        $this->assertEquals('value1', $this->storage->get('key1'));
    }

    public function testGetsTheDefaultValueWhenTheKeyIsNotFound(): void
    {
        $this->assertEquals('default', $this->storage->get('non_existing_key', 'default'));
    }

    public function testSetsAValueWithATtlAndExpiresIt(): void
    {
        $storage = $this->getMockBuilder(FileStorage::class)
            ->setConstructorArgs([['ttl' => 3600, 'path' => __DIR__ . '/../fixtures/cache']])
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

    public function testDeletesAValue(): void
    {
        $this->storage->set('key3', 'value3');
        $this->assertTrue($this->storage->delete('key3'));
        $this->assertFalse($this->storage->has('key3'));
    }

    public function testReturnsFalseWhenDeletingANonExistingKey(): void
    {
        $this->assertFalse($this->storage->delete('non_existing_key'));
    }

    public function testClearsAllValues(): void
    {
        $this->storage->set('key4', 'value4');
        $this->assertTrue($this->storage->clear());
        $this->assertFalse($this->storage->has('key4'));
    }

    public function testGetsMultipleValues(): void
    {
        $this->storage->set('key5', 'value5');
        $this->storage->set('key6', 'value6');
        $result = $this->storage->getMultiple(['key5', 'key6', 'non_existing_key'], 'default');

        $this->assertEquals(['key5' => 'value5', 'key6' => 'value6', 'non_existing_key' => 'default'], $result);
    }

    public function testSetsMultipleValues(): void
    {
        $values = ['key7' => 'value7', 'key8' => 'value8'];
        $this->assertTrue($this->storage->setMultiple($values));

        foreach ($values as $key => $value) {
            $this->assertEquals($value, $this->storage->get($key));
        }
    }

    public function testDeletesMultipleValues(): void
    {
        $this->storage->set('key9', 'value9');
        $this->storage->set('key10', 'value10');
        $this->assertTrue($this->storage->deleteMultiple(['key9', 'key10']));
        $this->assertFalse($this->storage->has('key9'));
        $this->assertFalse($this->storage->has('key10'));
    }

    public function testChecksWhetherAKeyExists(): void
    {
        $this->storage->set('key11', 'value11');
        $this->assertTrue($this->storage->has('key11'));
        $this->assertFalse($this->storage->has('non_existing_key'));
    }

    public function testIncrementsAValue(): void
    {
        $this->assertEquals(10, $this->storage->increment('key12', 10));
        $this->assertEquals(20, $this->storage->increment('key12', 10));
    }

    public function testDecrementsAValue(): void
    {
        $this->storage->set('key13', 20);
        $this->assertEquals(10, $this->storage->decrement('key13', 10));
    }

    public function testGetsTheInfoOfAKey(): void
    {
        $this->storage->set('key14', 'value14');
        $info = $this->storage->getInfo('key14');

        $this->assertArrayHasKey('value', $info);
        $this->assertEquals('value14', $info['value'] ?? null);
    }

    public function testRemembersAValue(): void
    {
        $value = $this->storage->remember('key1', fn (): string => 'value1', 1);

        $this->assertEquals('value1', $value);
    }

    public function testThrowsWhenTheCachePathIsMissing(): void
    {
        $this->expectException(CacheConfigurationException::class);

        new FileStorage(['ttl' => 3600]);
    }

    public function testThrowsWhenTheCachePathCannotBeCreated(): void
    {
        $this->expectException(CachePathException::class);

        set_error_handler(static fn (): bool => true);
        try {
            new FileStorage(['ttl' => 3600, 'path' => '/dev/null/nonexistent-cache-path']);
        } finally {
            restore_error_handler();
        }
    }

    public function testReturnsTheDefaultValueWhenTheCacheEntryCannotBeRead(): void
    {
        $path = (fn () => $this->{'makePath'}('key-read'))->call($this->storage);
        \mkdir($path, 0777, true);

        set_error_handler(static fn (): bool => true);
        try {
            $value = $this->storage->get('key-read', 'default');
            $info  = $this->storage->getInfo('key-read');
        } finally {
            restore_error_handler();
        }

        \rmdir($path);

        $this->assertSame('default', $value);
        $this->assertSame([], $info);
    }

    public function testReturnsTheDefaultValueWhenTheCacheFileIsNotReadable(): void
    {
        $this->storage->set('key-unreadable', 'value');
        $path = (fn () => $this->{'makePath'}('key-unreadable'))->call($this->storage);

        \chmod($path, 0);

        set_error_handler(static fn (): bool => true);
        try {
            $value = $this->storage->get('key-unreadable', 'default');
            $info  = $this->storage->getInfo('key-unreadable');
        } finally {
            restore_error_handler();
        }

        \chmod($path, 0644);
        \unlink($path);

        $this->assertSame('default', $value);
        $this->assertSame([], $info);
    }

    public function testReturnsTheDefaultValueWhenTheStoredDataIsNotAnArray(): void
    {
        $path = (fn () => $this->{'makePath'}('key-string'))->call($this->storage);
        \mkdir(dirname($path), 0777, true);
        file_put_contents($path, serialize('not-an-array'));

        $value = $this->storage->get('key-string', 'default');
        $info  = $this->storage->getInfo('key-string');

        \unlink($path);

        $this->assertSame('default', $value);
        $this->assertSame([], $info);
    }

    public function testReturnsTheDefaultValueWhenTheStoredTimestampIsNotAnInteger(): void
    {
        $path = (fn () => $this->{'makePath'}('key-timestamp'))->call($this->storage);
        \mkdir(dirname($path), 0777, true);
        file_put_contents($path, serialize(['value' => 'value', 'timestamp' => 'invalid', 'mtime' => 1.0]));

        $value = $this->storage->get('key-timestamp', 'default');

        \unlink($path);

        $this->assertSame('default', $value);
    }

    public function testThrowsWhenIncrementingANonIntegerValue(): void
    {
        $this->storage->set('key-inc', 'not-an-integer');

        $this->expectException(InvalidValueIncrementException::class);

        $this->storage->increment('key-inc', 1);
    }

    public function testReturnsAnEmptyInfoArrayForAnUnknownKey(): void
    {
        $this->assertSame([], $this->storage->getInfo('missing-key'));
    }

    public function testCalculatesTheExpirationTimestampFromADateTimeInstance(): void
    {
        $expiration = (fn () => $this->{'calculateExpirationTimestamp'}(
            new DateTimeImmutable('@1000000000')
        ))->call($this->storage);

        $this->assertSame(1000000000, $expiration);
    }

    public function testCalculatesTheExpirationTimestampFromADateInterval(): void
    {
        $expiration = (fn () => $this->{'calculateExpirationTimestamp'}(
            new DateInterval('PT1H')
        ))->call($this->storage);

        $this->assertGreaterThan(time(), $expiration);
    }

    public function testReportsSupportForTheFileStorage(): void
    {
        $this->assertTrue(FileStorage::isSupported());
    }
}
