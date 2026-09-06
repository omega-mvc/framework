<?php

/**
 * Part of Omega - Tests\Config Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Config;

use Omega\Config\ConfigRepository;
use Omega\Config\ConfigRepositoryInterface;
use Omega\Config\MergeStrategy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function class_implements;

/**
 * Class ConfigTest
 *
 * This test suite validates the behavior of the ConfigRepository component,
 * ensuring it provides correct access, mutation, and merging of configuration
 * values. The tests cover a variety of scenarios, including basic retrieval,
 * nested key resolution, default value handling, value removal, clearing the
 * repository, and merging configurations—both at the root level and within
 * nested structures.
 *
 * Through these tests, the reliability and consistency of the configuration
 * storage layer are verified, guaranteeing predictable behavior when used by
 * the framework or application code.
 *
 * @category  Tests
 * @package   Config
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(ConfigRepository::class)]
#[CoversClass(MergeStrategy::class)]
final class ConfigTest extends TestCase
{
    /**
     * The ConfigRepository instance used within each test.
     *
     * A fresh repository is created in setUp() before every test method to
     * guarantee isolation and reproducibility. Tests use this instance to verify
     * configuration retrieval, mutation, nested key handling, and merging behavior.
     *
     * @var ConfigRepository
     */
    private ConfigRepository $configuration;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->configuration = new ConfigRepository();
    }

    /**
     * Test it should implement config repository interface.
     *
     * @return void
     */
    public function testItShouldImplementConfigRepositoryInterface(): void
    {
        $this->assertContains(ConfigRepositoryInterface::class, class_implements($this->configuration::class));
    }

    /**
     * Test it should be empty by default.
     *
     * @return void
     */
    public function testItShouldBeEmptyByDefault(): void
    {
        $this->assertEmpty($this->configuration->getAll());
    }

    /**
     * Test it should accept store.
     *
     * @return void
     */
    public function testItShouldAcceptStore(): void
    {
        $store = ['key' => 'value'];
        $configuration = new ConfigRepository($store);

        $this->assertEquals($store, $configuration->getAll());
    }

    /**
     * Test it should return all values.
     *
     * @return void
     */
    public function testItShouldReturnAllValues(): void
    {
        $this->configuration->set('key', 'value');

        $this->assertEquals(['key' => 'value'], $this->configuration->getAll());
    }

    /**
     * Test it should determine if key has value.
     *
     * @return void
     */
    public function testItShouldDetermineIfKeyHasValue(): void
    {
        $this->assertFalse($this->configuration->has('key'));

        $this->configuration->set('key', 'value');

        $this->assertTrue($this->configuration->has('key'));
    }

    /**
     * Test it should determine if key jas nested value.
     *
     * @return void
     */
    public function testItShouldDetermineIfKeyHasNestedValue(): void
    {
        $this->assertFalse($this->configuration->has('nested.key'));

        $this->configuration->set('nested', ['key' => 'value']);

        $this->assertTrue($this->configuration->has('nested.key'));
    }

    /**
     * Test it should return null if key not found.
     *
     * @return void
     */
    public function testItShouldReturnNullIfKeyNotFound(): void
    {
        $this->assertNull($this->configuration->get('key'));
    }

    /**
     * Test it should return null if nested key not found.
     *
     * @return void
     */
    public function testItShouldReturnNullIfNestedKeyNotFound(): void
    {
        $this->assertNull($this->configuration->get('nested.key'));
    }

    /**
     * Test it should return default value if provided.
     *
     * @return void
     */
    public function testItShouldReturnDefaultValueIfProvided(): void
    {
        $this->assertEquals('test', $this->configuration->get('key', 'test'));
    }

    /**
     * Test it should return value for key.
     *
     * @return void
     */
    public function testItShouldReturnValueForKey(): void
    {
        $configuration = new ConfigRepository(['key' => 'value']);

        $this->assertEquals('value', $configuration->get('key'));
    }

    /**
     * Test it should return value for nested key.
     *
     * @return void
     */
    public function testItShouldReturnValueForNestedKey(): void
    {
        $configuration = new ConfigRepository(['nested' => ['key' => 'value']]);

        $this->assertEquals('value', $configuration->get('nested.key'));
    }

    /**
     * Test it should set value for key.
     *
     * @return void
     */
    public function testItShouldSetValueForKey(): void
    {
        $this->configuration->set('key', 'value');

        $this->assertEquals('value', $this->configuration->get('key'));
    }

    /**
     * Test it should set value for nested key.
     *
     * @return void
     */
    public function testItShouldSetValueForNestedKey(): void
    {
        $this->configuration->set('nested.key', 'value');

        $this->assertEquals(['key' => 'value'], $this->configuration->get('nested'));
    }
    /**
     * Test it should remove value for key.
     *
     * @return void
     */
    public function testItShouldRemoveValueForKey(): void
    {
        $configuration = new ConfigRepository(['key' => 'value']);
        $configuration->remove('key');

        $this->assertFalse($configuration->has('key'));

        // Make sure no exception is thrown if the key doesn't exist.
        $this->configuration->remove('non_existing_key');
    }

    /**
     * Test it should remove value for nested key.
     *
     * @return void
     */
    public function testItShouldRemoveValueForNestedKey(): void
    {
        $configuration = new ConfigRepository(['nested' => ['key' => 'value']]);
        $configuration->remove('nested.key');

        $this->assertFalse($configuration->has('nested.key'));
        $this->assertIsArray($configuration->get('nested'));
        $this->assertEmpty($configuration->get('nested'));

        // Make sure no exception is thrown if the key doesn't exist.
        $this->configuration->remove('nested.non_existing_key');
    }

    /**
     * Test it should clear all values.
     *
     * @return void
     */
    public function testItShouldClearAllValues(): void
    {
        $configuration = new ConfigRepository(['key' => 'value']);
        $configuration->clear();

        $this->assertEmpty($configuration->getAll());
    }

    /**
     * Test it should merge configuration.
     *
     * @return void
     */
    public function testItShouldMergeConfiguration(): void
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

    /**
     * Test it should merge configuration at key.
     *
     * @return void
     */
    public function testItShouldMergeConfigurationAtKey(): void
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

    /**
     * Test it should mergee configuration at nested key.
     *
     * @return void
     */
    public function testItShouldMergeConfigurationAtNestedKey(): void
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

    /**
     * Test it should merge configuration at existing key.
     *
     * @return void
     */
    public function testItShouldMergeConfigurationAtExistingKey(): void
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

    /**
     * Test it should merge associative arrays.
     *
     * @return void
     */
    public function testItShouldMergeAssociativeArrays(): void
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

    /**
     * Test it should replace indexed arrays.
     *
     * @return void
     */
    public function testItShouldReplaceIndexedArrays(): void
    {
        $configuration = new ConfigRepository(['indexed' => [1, 2, 3]]);
        $anotherConfiguration = new ConfigRepository(['indexed' => [1, 2]]);

        $configuration->merge($anotherConfiguration);

        $this->assertEquals(['indexed' => [1, 2]], $configuration->getAll());
    }

    /**
     * Test it should merge indexed arrays.
     *
     * @return void
     */
    public function testItShouldMergeIndexedArrays(): void
    {
        $configuration = new ConfigRepository(['indexed' => [1, 2, 3]]);
        $anotherConfiguration = new ConfigRepository(['indexed' => [3, 4, 5]]);

        $configuration->merge($anotherConfiguration, null, MergeStrategy::MERGE_INDEXED);
        $this->assertEquals(['indexed' => [1, 2, 3, 4, 5]], $configuration->getAll());
    }
}
