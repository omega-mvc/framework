<?php

declare(strict_types=1);

namespace Tests\Cache\Storage;

use InvalidArgumentException;
use Omega\Cache\Exceptions\CacheConfigurationException;
use Omega\Cache\Storage\ApcuStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(ApcuStorage::class)]
final class ApcuStorageTest extends TestCase
{
    public function testSetsAndGetsAValueFromTheApcuStorage(): void
    {
        $storage = self::cacheApcuStorage();

        if (null === $storage) {
            $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');
        }

        $this->assertTrue($storage->set('key1', 'value1'));
        $this->assertEquals('value1', $storage->get('key1'));
    }

    public function testGetsTheDefaultValueWhenTheKeyIsNotFound(): void
    {
        $storage = self::cacheApcuStorage();

        if (null === $storage) {
            $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');
        }

        $this->assertEquals('default', $storage->get('non_existing_key', 'default'));
    }

    public function testSetsAValueWithATtl(): void
    {
        $storage = self::cacheApcuStorage();

        if (null === $storage) {
            $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');
        }

        $this->assertTrue($storage->set('key2', 'value2', 1));
    }

    public function testDeletesAValueFromTheApcuStorage(): void
    {
        $storage = self::cacheApcuStorage();

        if (null === $storage) {
            $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');
        }

        $storage->set('key3', 'value3');
        $this->assertTrue($storage->delete('key3'));
        $this->assertFalse($storage->has('key3'));
    }

    public function testReturnsFalseWhenDeletingANonExistingKey(): void
    {
        $storage = self::cacheApcuStorage();

        if (null === $storage) {
            $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');
        }

        $this->assertFalse($storage->delete('non_existing_key'));
    }

    public function testClearsAllValuesFromTheApcuStorage(): void
    {
        $storage = self::cacheApcuStorage();

        if (null === $storage) {
            $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');
        }

        $storage->set('key4', 'value4');
        $this->assertTrue($storage->clear());
        $this->assertFalse($storage->has('key4'));
    }

    public function testGetsMultipleValuesFromTheApcuStorage(): void
    {
        $storage = self::cacheApcuStorage();

        if (null === $storage) {
            $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');
        }

        $storage->set('key5', 'value5');
        $storage->set('key6', 'value6');
        $result = $storage->getMultiple(['key5', 'key6', 'non_existing_key'], 'default');

        $this->assertEquals([
            'key5'             => 'value5',
            'key6'             => 'value6',
            'non_existing_key' => 'default',
        ], $result);
    }

    public function testSetsMultipleValuesToTheApcuStorage(): void
    {
        $storage = self::cacheApcuStorage();

        if (null === $storage) {
            $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');
        }

        $this->assertTrue($storage->setMultiple(['key7' => 'value7', 'key8' => 'value8']));
        $this->assertEquals('value7', $storage->get('key7'));
        $this->assertEquals('value8', $storage->get('key8'));
    }

    public function testDeletesMultipleValuesFromTheApcuStorage(): void
    {
        $storage = self::cacheApcuStorage();

        if (null === $storage) {
            $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');
        }

        $storage->set('key9', 'value9');
        $storage->set('key10', 'value10');
        $this->assertTrue($storage->deleteMultiple(['key9', 'key10']));
        $this->assertFalse($storage->has('key9'));
        $this->assertFalse($storage->has('key10'));
    }

    public function testChecksWhetherAKeyExistsInTheApcuStorage(): void
    {
        $storage = self::cacheApcuStorage();

        if (null === $storage) {
            $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');
        }

        $storage->set('key11', 'value11');
        $this->assertTrue($storage->has('key11'));
        $this->assertFalse($storage->has('non_existing_key'));
    }

    public function testIncrementsAValueInTheApcuStorage(): void
    {
        $storage = self::cacheApcuStorage();

        if (null === $storage) {
            $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');
        }

        $this->assertEquals(10, $storage->increment('key12', 10));
        $this->assertEquals(20, $storage->increment('key12', 10));
    }

    public function testDecrementsAValueInTheApcuStorage(): void
    {
        $storage = self::cacheApcuStorage();

        if (null === $storage) {
            $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');
        }

        $storage->increment('key13', 20);
        $this->assertEquals(10, $storage->decrement('key13', 10));
    }

    public function testGetsTheInfoOfAKeyFromTheApcuStorage(): void
    {
        $storage = self::cacheApcuStorage();

        if (null === $storage) {
            $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');
        }

        $storage->set('key14', 'value14');
        $info = $storage->getInfo('key14');

        $this->assertArrayHasKey('value', $info);
        $this->assertEquals('value14', $info['value'] ?? null);
        $this->assertArrayHasKey('timestamp', $info);
        $this->assertArrayHasKey('mtime', $info);
    }

    public function testRemembersAValueInTheApcuStorage(): void
    {
        $storage = self::cacheApcuStorage();

        if (null === $storage) {
            $this->markTestSkipped('APCu extension is not loaded or enabled for CLI.');
        }

        $value = $storage->remember('key1', fn (): string => 'value1', 1);
        $this->assertEquals('value1', $value);

        // second call should get from cache
        $value = $storage->remember('key1', fn (): string => 'value2', 1);
        $this->assertEquals('value1', $value);
    }

    public function testThrowsWhenThePrefixIsEmpty(): void
    {
        $this->expectException(CacheConfigurationException::class);

        new ApcuStorage(['ttl' => 3600]);
    }

    public function testReturnsAnEmptyInfoArrayForAnUnknownKey(): void
    {
        $storage = self::cacheApcuStorage();
        if (null === $storage) {
            $this->markTestSkipped('APCu is not available.');
        }

        $this->assertSame([], $storage->getInfo('unknown-key'));
    }

    public function testCalculatesTheTtlFromADateInterval(): void
    {
        $storage = self::cacheApcuStorage();
        if (null === $storage) {
            $this->markTestSkipped('APCu is not available.');
        }

        $this->assertTrue($storage->set('key1', 'value1', \DateInterval::createFromDateString('1 day')));
        $this->assertSame('value1', $storage->get('key1'));
    }

    public function testThrowsWhenIncrementingANonIntegerValue(): void
    {
        $storage = self::cacheApcuStorage();
        if (null === $storage) {
            $this->markTestSkipped('APCu is not available.');
        }

        $storage->set('key1', 'not-an-integer');

        $this->expectException(InvalidArgumentException::class);

        $storage->increment('key1', 1);
    }

    private static function cacheApcuStorage(): ?ApcuStorage
    {
        if (!ApcuStorage::isSupported()) {
            return null;
        }

        $storage = new ApcuStorage(['ttl' => 3600, 'prefix' => 'test_']);
        $storage->clear();

        return $storage;
    }
}
