<?php

declare(strict_types=1);

namespace Tests\Cache\Storage;

use DateInterval;
use DateTime;
use InvalidArgumentException;
use Omega\Cache\Exceptions\CacheConfigurationException;
use Omega\Cache\Storage\MemoryStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function time;

#[CoversClass(MemoryStorage::class)]
final class MemoryTest extends TestCase
{
    private MemoryStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new MemoryStorage(['ttl' => 3600]);
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

    public function testSetsAValueWithATtlAndExpiresItWithoutSleeping(): void
    {
        $key   = 'key2';
        $value = 'value2';

        $this->assertTrue($this->storage->set($key, $value, 1));
        $this->assertEquals($value, $this->storage->get($key));

        $past = DateInterval::createFromDateString('-1 second');
        $this->storage->set($key, $value, $past);

        $this->assertNull($this->storage->get($key));
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
        $this->assertTrue($this->storage->setMultiple(['key7' => 'value7', 'key8' => 'value8']));
        $this->assertEquals('value7', $this->storage->get('key7'));
        $this->assertEquals('value8', $this->storage->get('key8'));
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
        $this->storage->increment('key13', 20);
        $this->assertEquals(10, $this->storage->decrement('key13', 10));
    }

    public function testGetsTheInfoOfAKey(): void
    {
        $this->storage->set('key14', 'value14');
        $info = $this->storage->getInfo('key14');

        $this->assertArrayHasKey('value', $info);
        $this->assertEquals('value14', $info['value'] ?? null);
    }

    public function testCalculatesTheExpirationTimestamp(): void
    {
        $time = time();

        $expired = (fn () => $this->{'calculateExpirationTimestamp'}(null))->call($this->storage);
        $this->assertGreaterThanOrEqual($time, $expired);

        $expired = (fn () => $this->{'calculateExpirationTimestamp'}(time()))->call($this->storage);
        $this->assertGreaterThanOrEqual($time, $expired);

        $expired = (
            fn () => $this->{'calculateExpirationTimestamp'}(DateInterval::createFromDateString('1 day'))
        )->call($this->storage);
        $this->assertGreaterThanOrEqual($time, $expired);

        $expired = (fn () => $this->{'calculateExpirationTimestamp'}(new DateTime()))->call($this->storage);
        $this->assertGreaterThanOrEqual($time, $expired);
    }

    public function testReportsWhetherAnItemIsExpired(): void
    {
        $expired = (fn () => $this->{'isExpired'}(time() + 2))->call($this->storage);

        $this->assertFalse($expired);
    }

    public function testCreatesAFloatMtime(): void
    {
        $mtime = (fn () => $this->{'createMtime'}())->call($this->storage);

        $this->assertGreaterThan(0.0, $mtime);
    }

    public function testRemembersAValue(): void
    {
        $value = $this->storage->remember('key1', fn (): string => 'value1', 1);

        $this->assertEquals('value1', $value);
    }

    public function testThrowsWhenTheDefaultTtlIsNegative(): void
    {
        $this->expectException(CacheConfigurationException::class);

        new MemoryStorage(['ttl' => -1]);
    }

    public function testEvictsTheLeastRecentlyWrittenItemWhenTheStoreIsFull(): void
    {
        $storage = new MemoryStorage(['ttl' => 3600, 'maxItems' => 2]);

        $storage->set('first', 'value1');
        \usleep(2000);
        $storage->set('second', 'value2');
        \usleep(2000);
        $storage->set('third', 'value3');

        $this->assertFalse($storage->has('first'));
        $this->assertTrue($storage->has('second'));
        $this->assertTrue($storage->has('third'));
    }

    public function testThrowsWhenIncrementingANonIntegerValue(): void
    {
        $this->storage->set('key', 'not-an-integer');

        $this->expectException(InvalidArgumentException::class);

        $this->storage->increment('key', 1);
    }

    public function testReportsSupportForTheMemoryStorage(): void
    {
        $this->assertTrue(MemoryStorage::isSupported());
    }

    public function testGarbageCollectsExpiredItems(): void
    {
        $this->storage->set('expired', 'value', 0);
        $this->storage->set('fresh', 'value', 3600);

        $this->assertFalse($this->storage->has('expired'));
        $this->assertTrue($this->storage->has('fresh'));
    }

    public function testReturnsTheCachedValueOnASecondRemember(): void
    {
        $first  = $this->storage->remember('key1', fn (): string => 'first', 3600);
        $second = $this->storage->remember('key1', fn (): string => 'second', 3600);

        $this->assertSame('first', $first);
        $this->assertSame('first', $second);
    }
}
