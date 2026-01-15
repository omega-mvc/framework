<?php

declare(strict_types=1);

namespace Tests\Config;

use Omega\Config\ConfigRepository;
use Omega\Config\ConfigRepositoryInterface;
use Omega\Config\MergeStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function class_implements;

#[CoversClass(ConfigRepository::class)]
#[CoversClass(MergeStrategy::class)]
class ConfigTest extends TestCase
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
        $store = ['key' => 'value'];
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
        $this->assertEquals('test', $this->configuration->get('key', 'test'));
    }

    public function testShouldReturnValueForKey(): void
    {
        $configuration = new ConfigRepository(['key' => 'value']);

        $this->assertEquals('value', $configuration->get('key'));
    }

    public function testShouldReturnValueForNestedKey(): void
    {
        $configuration = new ConfigRepository(['nested' => ['key' => 'value']]);

        $this->assertEquals('value', $configuration->get('nested.key'));
    }

    public function testShouldSetValueForKey(): void
    {
        $this->configuration->set('key', 'value');

        $this->assertEquals('value', $this->configuration->get('key'));
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

        // Make sure no exception is thrown if the key doesn't exist.
        $this->configuration->remove('non_existing_key');
    }

    public function testShouldRemoveValueForNestedKey(): void
    {
        $configuration = new ConfigRepository(['nested' => ['key' => 'value']]);
        $configuration->remove('nested.key');

        $this->assertFalse($configuration->has('nested.key'));
        $this->assertIsArray($configuration->get('nested'));
        $this->assertEmpty($configuration->get('nested'));

        // Make sure no exception is thrown if the key doesn't exist.
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
        $configuration = new ConfigRepository(['key' => 'value']);
        $anotherConfiguration = new ConfigRepository(['another_key' => 'another_value']);

        $configuration->merge($anotherConfiguration);

        $this->assertEquals(
            [
                'key'         => 'value',
                'another_key' => 'another_value',
            ],
            $configuration->getAll()
        );
    }

    public function testShouldMergeConfigurationAtKey(): void
    {
        $configuration = new ConfigRepository(['key' => 'value']);
        $anotherConfiguration = new ConfigRepository(['another_key' => 'another_value']);

        $configuration->merge($anotherConfiguration, 'nested');

        $this->assertEquals(
            [
                'key'    => 'value',
                'nested' => [
                    'another_key' => 'another_value',
                ],
            ],
            $configuration->getAll()
        );
    }

    public function testShouldMergeConfigurationAtNestedKey(): void
    {
        $configuration = new ConfigRepository(['key' => 'value']);
        $anotherConfiguration = new ConfigRepository(['another_key' => 'another_value']);

        $configuration->merge($anotherConfiguration, 'nested.section');

        $this->assertEquals(
            [
                'key'    => 'value',
                'nested' => [
                    'section' => [
                        'another_key' => 'another_value',
                    ],
                ],
            ],
            $configuration->getAll()
        );
    }

    public function testShouldMergeConfigurationAtExistingKey(): void
    {
        $configuration = new ConfigRepository(['nested' => ['key' => 'value']]);
        $anotherConfiguration = new ConfigRepository(['another_key' => 'another_value']);

        $configuration->merge($anotherConfiguration, 'nested.section');

        $this->assertEquals(
            [
                'nested' => [
                    'key'     => 'value',
                    'section' => [
                        'another_key' => 'another_value',
                    ],
                ],
            ],
            $configuration->getAll()
        );
    }

    public function testShouldMergeAssociativeArrays(): void
    {
        $configuration = new ConfigRepository(['nested' => ['key' => 'value']]);
        $anotherConfiguration = new ConfigRepository(['nested' => ['another_key' => 'another_value']]);

        $configuration->merge($anotherConfiguration);

        $this->assertEquals(
            [
                'nested' => [
                    'key'         => 'value',
                    'another_key' => 'another_value',
                ],
            ],
            $configuration->getAll()
        );
    }

    public function testShouldReplaceIndexedArrays(): void
    {
        $configuration = new ConfigRepository(['indexed' => [1, 2, 3]]);
        $anotherConfiguration = new ConfigRepository(['indexed' => [1, 2]]);

        $configuration->merge($anotherConfiguration);

        $this->assertEquals(['indexed' => [1, 2]], $configuration->getAll());
    }

    public function testShouldMergeIndexedArrays(): void
    {
        $configuration = new ConfigRepository(['indexed' => [1, 2, 3]]);
        $anotherConfiguration = new ConfigRepository(['indexed' => [3, 4, 5]]);

        $configuration->merge($anotherConfiguration, null, MergeStrategy::MERGE_INDEXED);
        $this->assertEquals(['indexed' => [1, 2, 3, 4, 5]], $configuration->getAll());
    }
}
