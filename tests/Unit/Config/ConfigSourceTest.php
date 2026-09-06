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

use Omega\Config\ConfigSource;
use Omega\Config\MergeStrategy;
use Omega\Config\Source\ArrayConfig;
use Omega\Config\Source\JsonConfig;
use Omega\Config\Source\XmlConfig;
use Omega\Macroable\Exceptions\MacroNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function json_encode;
use function sys_get_temp_dir;
use function uniqid;

/**
 * Class ConfigSourceTest
 *
 * This test suite verifies the behavior of the {@see ConfigSource} service: a
 * conveniente, macro-ready aggregator over `ConfigBuilder` and the `Source\`
 * implementations. It ensures array, JSON, and XML sources can be combined, that
 * sections and priorities are respected, that merge strategies are honoured, and
 * that the macro surface works as advertised.
 *
 * @category  Tests
 * @package   Config
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(ConfigSource::class)]
#[CoversClass(ArrayConfig::class)]
#[CoversClass(JsonConfig::class)]
#[CoversClass(XmlConfig::class)]
final class ConfigSourceTest extends TestCase
{
    /**
     * ConfigSource instance used across the test methods.
     *
     * @var ConfigSource
     */
    private ConfigSource $source;

    /**
     * Sets up the environment before each test method.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->source = new ConfigSource();
        ConfigSource::resetMacro();
    }

    /**
     * Test it should return an empty configuration when no sources are added.
     *
     * @return void
     */
    public function testItShouldReturnEmptyConfigurationWhenNoSources(): void
    {
        $this->assertEmpty($this->source->build()->getAll());
    }

    /**
     * Test it should build from an in-memory array source.
     *
     * @return void
     */
    public function testItShouldBuildFromArraySource(): void
    {
        $config = $this->source->fromArray(['app' => ['debug' => true]])->build();

        $this->assertSame(true, $config->get('app.debug'));
    }

    /**
     * Test it should load a JSON file source.
     *
     * @return void
     */
    public function testItShouldLoadJsonFileSource(): void
    {
        $file = $this->writeTempFile(json_encode(['app' => ['name' => 'Omega']]));

        $config = $this->source->fromJson($file)->build();

        $this->assertSame('Omega', $config->get('app.name'));
    }

    /**
     * Test it should load an XML file source.
     *
     * @return void
     */
    public function testItShouldLoadXmlFileSource(): void
    {
        $file = $this->writeTempFile('<?xml version="1.0"?><app><name>Omega</name></app>');

        $config = $this->source->fromXml($file)->build();

        $this->assertSame('Omega', $config->get('name'));
    }

    /**
     * Test it should merge multiple sources recursively.
     *
     * @return void
     */
    public function testItShouldMergeMultipleSourcesRecursively(): void
    {
        $config = $this->source
            ->fromArray(['nested' => ['key' => 'value']])
            ->fromArray(['nested' => ['other' => 'other_value']])
            ->build();

        $this->assertEquals(
            ['nested' => ['key' => 'value', 'other' => 'other_value']],
            $config->getAll()
        );
    }

    /**
     * Test it should support section grouping.
     *
     * @return void
     */
    public function testItShouldSupportSectionGrouping(): void
    {
        $config = $this->source
            ->fromArray(['key' => 'value'])
            ->fromArray(['secret' => 'x'], 'secrets')
            ->build();

        $this->assertEquals(
            [
                'key'     => 'value',
                'secrets' => ['secret' => 'x'],
            ],
            $config->getAll()
        );
    }

    /**
     * Test it should honour the merge strategy override.
     *
     * @return void
     */
    public function testItShouldHonourMergeStrategyOverrides(): void
    {
        $config = $this->source
            ->fromArray(['items' => [1, 2, 3]])
            ->fromArray(['items' => [3, 4, 5]])
            ->build(MergeStrategy::MERGE_INDEXED);

        $this->assertEquals(['items' => [1, 2, 3, 4, 5]], $config->getAll());
    }

    /**
     * Test it should give higher priority sources precedence.
     *
     * @return void
     */
    public function testItShouldGiveHigherPrioritySourcesPrecedence(): void
    {
        $config = $this->source
            ->fromArray(['debug' => false], null, 10)
            ->fromArray(['debug' => true], null, 50)
            ->build();

        $this->assertSame(true, $config->get('debug'));
    }

    /**
     * Test it should execute a registered macro.
     *
     * @return void
     */
    public function testItShouldExecuteRegisteredMacro(): void
    {
        ConfigSource::macro('fromYaml', function (array $content): ConfigSource {
            return $this->fromArray($content);
        });

        $source = new ConfigSource();
        $config = $source->fromYaml(['app' => ['name' => 'Yaml']])->build();

        $this->assertSame('Yaml', $config->get('app.name'));
    }

    /**
     * Test it should report registered macros.
     *
     * @return void
     */
    public function testItShouldReportRegisteredMacros(): void
    {
        ConfigSource::macro('myMacro', fn () => null);

        $this->assertTrue(ConfigSource::hasMacro('myMacro'));
    }

    /**
     * Test it should throw when an unknown macro is called.
     *
     * @return void
     */
    public function testItShouldThrowWhenUnknownMacroCalled(): void
    {
        $this->expectException(MacroNotFoundException::class);

        (new ConfigSource())->doesNotExist();
    }

    /**
     * Write a temporary file and return its path.
     *
     * @param string $content The file content.
     * @return string The temporary file path.
     */
    private function writeTempFile(string $content): string
    {
        $path = sys_get_temp_dir() . '/omega-config-source-' . uniqid() . '.tmp';
        file_put_contents($path, $content);

        return $path;
    }
}
