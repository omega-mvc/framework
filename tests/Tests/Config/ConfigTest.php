<?php

declare(strict_types=1);

namespace Tests\Config;

use Omega\Config\ConfigRepository;
use Omega\Config\ConfigRepositoryInterface;
use Omega\Config\MergeStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function class_implements;

#[CoversClass(ConfigRepository::class)]
#[CoversClass(MergeStrategy::class)]
final class ConfigTest extends TestCase
{
    private ConfigRepository $configuration;

    protected function setUp(): void
    {
        $this->configuration = new ConfigRepository();
    }

    public function testShouldImplementConfigRepositoryInterface(): void
    {
        $this->assertContains(ConfigRepositoryInterface::class, class_implements($this->configuration::class));
    }

    public function testShouldBeEmptyByDefault(): void
    {
        $this->assertEmpty($this->configuration->getAll());
    }

    public function testShouldAcceptStore(): void
    {
        $store         = ['key' => 'value'];
        $configuration = new ConfigRepository($store);

        $this->assertEquals($store, $configuration->getAll());
    }

    public function testShouldReturnAllValues(): void
    {
        $this->configuration->set('key', 'value');

        $this->assertEquals(['key' => 'value'], $this->configuration->getAll());
    }

    public function testShouldDetermineIfKeyHasValue(): void
    {
        $this->assertFalse($this->configuration->has('key'));

        $this->configuration->set('key', 'value');

        $this->assertTrue($this->configuration->has('key'));
    }

    public function testShouldDetermineIfKeyHasNestedValue(): void
    {
        $this->assertFalse($this->configuration->has('nested.key'));

        $this->configuration->set('nested', ['key' => 'value']);

        $this->assertTrue($this->configuration->has('nested.key'));
    }

    public function testShouldReturnNullIfKeyNotFound(): void
    {
        $this->assertNull($this->configuration->get('key'));
    }

    public function testShouldReturnNullIfNestedKeyNotFound(): void
    {
        $this->assertNull($this->configuration->get('nested.key'));
    }

    public function testShouldReturnDefaultValueIfProvided(): void
    {
        $this->assertSame('test', $this->configuration->get('key', 'test'));
    }

    public function testShouldReturnValueForKey(): void
    {
        $configuration = new ConfigRepository(['key' => 'value']);

        $this->assertSame('value', $configuration->get('key'));
    }

    public function testShouldReturnValueForNestedKey(): void
    {
        $configuration = new ConfigRepository(['nested' => ['key' => 'value']]);

        $this->assertSame('value', $configuration->get('nested.key'));
    }

    public function testShouldSetValueForKey(): void
    {
        $this->configuration->set('key', 'value');

        $this->assertSame('value', $this->configuration->get('key'));
    }

    public function testShouldSetValueForNestedKey(): void
    {
        $this->configuration->set('nested.key', 'value');

        $this->assertEquals(['key' => 'value'], $this->configuration->get('nested'));
    }

    public function testShouldRemoveValueForKey(): void
    {
        $configuration = new ConfigRepository(['key' => 'value']);
        $configuration->remove('key');

        $this->assertFalse($configuration->has('key'));

        $this->configuration->remove('non_existing_key');
    }

    public function testShouldRemoveValueForNestedKey(): void
    {
        $configuration = new ConfigRepository(['nested' => ['key' => 'value']]);
        $configuration->remove('nested.key');

        $this->assertFalse($configuration->has('nested.key'));
        $this->assertIsArray($configuration->get('nested'));
        $this->assertEmpty($configuration->get('nested'));

        $this->configuration->remove('nested.non_existing_key');
    }

    public function testShouldClearAllValues(): void
    {
        $configuration = new ConfigRepository(['key' => 'value']);
        $configuration->clear();

        $this->assertEmpty($configuration->getAll());
    }

    public function testShouldMergeConfiguration(): void
    {
        $configuration        = new ConfigRepository(['key' => 'value']);
        $anotherConfiguration = new ConfigRepository(['another_key' => 'another_value']);

        $configuration->merge($anotherConfiguration);

        $this->assertEquals([
            'key'         => 'value',
            'another_key' => 'another_value',
        ], $configuration->getAll());
    }

    public function testShouldMergeConfigurationAtKey(): void
    {
        $configuration        = new ConfigRepository(['key' => 'value']);
        $anotherConfiguration = new ConfigRepository(['another_key' => 'another_value']);

        $configuration->merge($anotherConfiguration, 'nested');

        $this->assertEquals([
            'key'    => 'value',
            'nested' => [
                'another_key' => 'another_value',
            ],
        ], $configuration->getAll());
    }

    public function testShouldMergeConfigurationAtNestedKey(): void
    {
        $configuration        = new ConfigRepository(['key' => 'value']);
        $anotherConfiguration = new ConfigRepository(['another_key' => 'another_value']);

        $configuration->merge($anotherConfiguration, 'nested.section');

        $this->assertEquals([
            'key'    => 'value',
            'nested' => [
                'section' => [
                    'another_key' => 'another_value',
                ],
            ],
        ], $configuration->getAll());
    }

    public function testShouldMergeConfigurationAtExistingKey(): void
    {
        $configuration        = new ConfigRepository(['nested' => ['key' => 'value']]);
        $anotherConfiguration = new ConfigRepository(['another_key' => 'another_value']);

        $configuration->merge($anotherConfiguration, 'nested.section');

        $this->assertEquals([
            'nested' => [
                'key'     => 'value',
                'section' => [
                    'another_key' => 'another_value',
                ],
            ],
        ], $configuration->getAll());
    }

    public function testShouldMergeAssociativeArrays(): void
    {
        $configuration        = new ConfigRepository(['nested' => ['key' => 'value']]);
        $anotherConfiguration = new ConfigRepository(['nested' => ['another_key' => 'another_value']]);

        $configuration->merge($anotherConfiguration);

        $this->assertEquals([
            'nested' => [
                'key'         => 'value',
                'another_key' => 'another_value',
            ],
        ], $configuration->getAll());
    }

    public function testShouldReplaceIndexedArrays(): void
    {
        $configuration        = new ConfigRepository(['indexed' => [1, 2, 3]]);
        $anotherConfiguration = new ConfigRepository(['indexed' => [1, 2]]);

        $configuration->merge($anotherConfiguration);

        $this->assertEquals(['indexed' => [1, 2]], $configuration->getAll());
    }

    public function testShouldMergeIndexedArrays(): void
    {
        $configuration        = new ConfigRepository(['indexed' => [1, 2, 3]]);
        $anotherConfiguration = new ConfigRepository(['indexed' => [3, 4, 5]]);

        $configuration->merge($anotherConfiguration, null, MergeStrategy::MERGE_INDEXED);

        $this->assertEquals(['indexed' => [1, 2, 3, 4, 5]], $configuration->getAll());
    }

    public function testShouldMergeConfigurationAtKeyWithStrategyInstance(): void
    {
        $configuration        = new ConfigRepository(['nested' => ['indexed' => [1, 2, 3]]]);
        $anotherConfiguration = new ConfigRepository(['indexed' => [3, 4, 5]]);

        $configuration->merge($anotherConfiguration, 'nested', MergeStrategy::MERGE_INDEXED);

        $this->assertSame(['indexed' => [1, 2, 3, 4, 5]], $configuration->get('nested'));
    }

    public function testShouldAddNewKeysWithTheMergeAddNewStrategy(): void
    {
        $configuration        = new ConfigRepository(['existing' => 'old']);
        $anotherConfiguration = new ConfigRepository(['existing' => 'new', 'added' => 'value']);

        $configuration->merge($anotherConfiguration, null, MergeStrategy::MERGE_ADD_NEW);

        $this->assertSame('new', $configuration->get('existing'));
        $this->assertSame('value', $configuration->get('added'));
    }

    public function testShouldMergeEmptyConfiguration(): void
    {
        $configuration = new ConfigRepository(['key' => 'value']);

        $configuration->merge(new ConfigRepository());

        $this->assertSame(['key' => 'value'], $configuration->getAll());
    }

    public function testShouldAddNewKeysWhenValuesAreIndexedArrays(): void
    {
        $configuration        = new ConfigRepository(['indexed' => [1, 2]]);
        $anotherConfiguration = new ConfigRepository(['indexed' => [3], 'added' => 'value']);

        $configuration->merge($anotherConfiguration, null, MergeStrategy::MERGE_ADD_NEW);

        $this->assertSame(['indexed' => [3], 'added' => 'value'], $configuration->getAll());
    }

    public function testShouldAddNewKeysWhenFirstValueIsArray(): void
    {
        $configuration        = new ConfigRepository(['mixed' => ['a' => 1]]);
        $anotherConfiguration = new ConfigRepository(['mixed' => 'scalar', 'added' => 'value']);

        $configuration->merge($anotherConfiguration, null, MergeStrategy::MERGE_ADD_NEW);

        $this->assertSame(['mixed' => 'scalar', 'added' => 'value'], $configuration->getAll());
    }

    public function testShouldAddNewKeysWhenSecondValueIsArray(): void
    {
        $configuration        = new ConfigRepository(['mixed' => 'scalar']);
        $anotherConfiguration = new ConfigRepository(['mixed' => ['a' => 1], 'added' => 'value']);

        $configuration->merge($anotherConfiguration, null, MergeStrategy::MERGE_ADD_NEW);

        $this->assertSame(['mixed' => ['a' => 1], 'added' => 'value'], $configuration->getAll());
    }

    public function testShouldAddNewKeysWhenValuesAreAssociative(): void
    {
        $configuration        = new ConfigRepository(['nested' => ['a' => 1]]);
        $anotherConfiguration = new ConfigRepository(['nested' => ['b' => 2], 'added' => 'value']);

        $configuration->merge($anotherConfiguration, null, MergeStrategy::MERGE_ADD_NEW);

        $this->assertSame([
            'nested' => ['a' => 1, 'b' => 2],
            'added'  => 'value',
        ], $configuration->getAll());
    }

    public function testShouldAddNewKeysWhenAllKeysAlreadyExist(): void
    {
        $configuration        = new ConfigRepository(['a' => 1, 'b' => 2]);
        $anotherConfiguration = new ConfigRepository(['a' => 3, 'b' => 4]);

        $configuration->merge($anotherConfiguration, null, MergeStrategy::MERGE_ADD_NEW);

        $this->assertSame(['a' => 3, 'b' => 4], $configuration->getAll());
    }

    public function testShouldPushValuesOntoAKey(): void
    {
        $this->configuration->push('items', 'first');
        $this->configuration->push('items', 'second');

        $this->assertSame(['first', 'second'], $this->configuration->get('items'));
    }

    public function testShouldPushValuesOntoANonArrayKey(): void
    {
        $configuration = new ConfigRepository(['items' => 'scalar']);

        $configuration->push('items', 'first');

        $this->assertSame(['first'], $configuration->get('items'));
    }

    public function testShouldSupportArrayAccess(): void
    {
        $this->configuration['key'] = 'value';

        $this->assertTrue(isset($this->configuration['key']));
        $this->assertSame('value', $this->configuration['key']);

        unset($this->configuration['key']);

        $this->assertFalse(isset($this->configuration['key']));
    }

    public function testShouldBeCountableAndIterable(): void
    {
        $this->configuration->set('first', 1);
        $this->configuration->set('second', 2);

        $this->assertCount(2, $this->configuration);

        $values = [];
        foreach ($this->configuration as $value) {
            $values[] = $value;
        }

        $this->assertSame([1, 2], $values);
    }

    public function testShouldReturnFalseWhenAnIntermediateSectionIsNotAnArray(): void
    {
        $configuration = new ConfigRepository(['nested' => 'scalar']);

        $this->assertFalse($configuration->has('nested.key'));
        $this->assertSame('default', $configuration->get('nested.key', 'default'));
    }

    public function testShouldReturnNullForAnExistingNullValue(): void
    {
        $configuration = new ConfigRepository(['key' => null]);

        $this->assertTrue($configuration->has('key'));
        $this->assertNull($configuration->get('key', 'default'));
    }

    public function testShouldReadADeepNestedValue(): void
    {
        $configuration = new ConfigRepository(['a' => ['b' => ['c' => 3]]]);

        $this->assertTrue($configuration->has('a.b.c'));
        $this->assertSame(3, $configuration->get('a.b.c'));
    }

    public function testShouldReplaceAScalarIntermediateSectionOnSet(): void
    {
        $configuration = new ConfigRepository(['nested' => 'scalar']);

        $configuration->set('nested.key', 'value');

        $this->assertSame(['nested' => ['key' => 'value']], $configuration->getAll());
    }

    public function testShouldCreateADeepNestedValueOverAScalar(): void
    {
        $configuration = new ConfigRepository(['a' => ['b' => 'scalar']]);

        $configuration->set('a.b.c', 'value');

        $this->assertSame(['a' => ['b' => ['c' => 'value']]], $configuration->getAll());
    }

    public function testShouldIgnoreRemoveWhenAnIntermediateSectionIsNotAnArray(): void
    {
        $configuration = new ConfigRepository(['nested' => 'scalar']);

        $configuration->remove('nested.key');

        $this->assertSame(['nested' => 'scalar'], $configuration->getAll());
    }

    public function testShouldInitializeANullKeyOnPush(): void
    {
        $configuration = new ConfigRepository(['items' => null]);

        $configuration->push('items', 'first');

        $this->assertSame(['items' => ['first']], $configuration->getAll());
    }
}
