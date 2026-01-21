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

use Omega\Config\ConfigBuilder;
use Omega\Config\MergeStrategy;
use Omega\Config\Source\ArrayConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Class ConfigBuilderTest
 *
 * This test suite verifies the behavior of the ConfigBuilder component,
 * ensuring it correctly aggregates, merges, and transforms configuration
 * sources into a final configuration structure. The tests cover scenarios such
 * as handling empty configurations, recursive merging, replacement of indexed
 * arrays, and merging configuration sources into specific or nested keys.
 *
 * The goal is to guarantee that ConfigBuilder behaves predictably under
 * various merging strategies and input combinations, producing a consistent and
 * reliable configuration array for the framework.
 *
 * @category  Tests
 * @package   Config
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(ConfigBuilder::class)]
#[CoversClass(MergeStrategy::class)]
#[CoversClass(ArrayConfig::class)]
class ConfigBuilderTest extends TestCase
{
    /**
     * Instance of the ConfigBuilder used across test methods.
     *
     * This builder is initialized fresh for each test in setUp(), ensuring
     * isolation between test cases and providing a clean environment to verify
     * configuration aggregation, merging behavior, and nested insertion logic.
     *
     * @var ConfigBuilder
     */
    private ConfigBuilder $builder;

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
        $this->builder = new ConfigBuilder();
    }

    /**
     * Test it should roduce empty configuration object if no sources.
     *
     * @return void
     */
    public function testItShouldProduceEmptyConfigurationObjectIfNoSources(): void
    {
        $this->assertEmpty($this->builder->build()->getAll());
    }

    /**
     * Test it should accept configuration source.
     *
     * @return void
     */
    public function testItShouldAcceptConfigurationSource(): void
    {
        $content = ['key' => 'value'];

        $this->assertEquals(
            $content,
            $this->builder
                ->addConfiguration(new ArrayConfig($content))
                ->build()
                ->getAll()
        );
    }

    /**
     * Test it should merge configuration source contents.
     *
     * @return void
     */
    public function testItShouldMergeConfigurationSourceContents(): void
    {
        $source_1 = new ArrayConfig(['key' => 'value']);
        $source_2 = new ArrayConfig(['another_key' => 'another_value']);

        $this->assertEquals(
            [
                'key'         => 'value',
                'another_key' => 'another_value',
            ],
            $this->builder
                ->addConfiguration($source_1)
                ->addConfiguration($source_2)
                ->build()
                ->getAll()
        );
    }

    /**
     * Test it should merge configuration source contents recursively.
     *
     * @return void
     */
    public function testItShouldMergeConfigurationSourceContentsRecursively(): void
    {
        $source_1 = new ArrayConfig(['nested' => ['key' => 'value']]);
        $source_2 = new ArrayConfig(['nested' => ['another_key' => 'another_value']]);

        $this->assertEquals(
            [
                'nested' => [
                    'key'         => 'value',
                    'another_key' => 'another_value',
                ],
            ],
            $this->builder
                ->addConfiguration($source_1)
                ->addConfiguration($source_2)
                ->build()
                ->getAll()
        );
    }

    /**
     * Test it should replace indexed arrays in configuration source contents.
     *
     * @return void
     */
    public function testItShouldReplaceIndexedArraysInConfigurationSourceContents(): void
    {
        $source_1 = new ArrayConfig(['key' => [1, 2, 3]]);
        $source_2 = new ArrayConfig(['key' => [1, 2]]);

        $this->assertEquals(
            [
                'key' => [1, 2],
            ],
            $this->builder
                ->addConfiguration($source_1)
                ->addConfiguration($source_2)
                ->build()
                ->getAll()
        );
    }

    /**
     * Test it should merge indexed arrays configuration source contents.
     *
     * @return void
     */
    public function testItShouldMergeIndexedArraysInConfigurationSourceContents(): void
    {
        $source_1 = new ArrayConfig(['key' => [1, 2, 3]]);
        $source_2 = new ArrayConfig(['key' => [3, 4, 5]]);

        $this->assertEquals(
            [
                'key' => [1, 2, 3, 4, 5],
            ],
            $this->builder
                ->addConfiguration($source_1)
                ->addConfiguration($source_2)
                ->build(MergeStrategy::from(MergeStrategy::MERGE_INDEXED))
                ->getAll()
        );
    }

    /**
     * Test it should merge configuration source contents array.
     *
     * @return void
     */
    public function testItShouldMergeConfigurationSourceContentsAtKey(): void
    {
        $source_1 = new ArrayConfig(['key' => 'value']);
        $source_2 = new ArrayConfig(['another_key' => 'another_value']);

        $this->assertEquals(
            [
                'key'    => 'value',
                'nested' => [
                    'another_key' => 'another_value',
                ],
            ],
            $this->builder
                ->addConfiguration($source_1)
                ->addConfiguration($source_2, 'nested')
                ->build()
                ->getAll()
        );
    }

    /**
     * Test it should merge configuration source contents as nested key.
     *
     * @return void
     */
    public function testItShouldMergeConfigurationSourceContentsAtNestedKey(): void
    {
        $source_1 = new ArrayConfig(['key' => 'value']);
        $source_2 = new ArrayConfig(['another_key' => 'another_value']);

        $this->assertEquals(
            [
                'key'    => 'value',
                'nested' => [
                    'section' => [
                        'another_key' => 'another_value',
                    ],
                ],
            ],
            $this->builder
                ->addConfiguration($source_1)
                ->addConfiguration($source_2, 'nested.section')
                ->build()
                ->getAll()
        );
    }

    /**
     * Test it should merge configuration source contents as existing key.
     *
     * @return void
     */
    public function testItShouldMergeConfigurationSourceContentsAtExistingKey(): void
    {
        $source_1 = new ArrayConfig(['nested' => ['key' => 'value']]);
        $source_2 = new ArrayConfig(['another_key' => 'another_value']);

        $this->assertEquals(
            [
                'nested' => [
                    'key'         => 'value',
                    'another_key' => 'another_value',
                ],
            ],
            $this->builder
                ->addConfiguration($source_1)
                ->addConfiguration($source_2, 'nested')
                ->build()
                ->getAll()
        );
    }
}
