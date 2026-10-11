<?php

declare(strict_types=1);

namespace Tests\Config;

use Omega\Config\ConfigSource;
use Omega\Config\Exceptions\MalformedXmlException;
use Omega\Config\MergeStrategy;
use Omega\Config\Source\ArrayConfig;
use Omega\Config\Source\JsonConfig;
use Omega\Config\Source\XmlConfig;
use Omega\Macroable\Exceptions\MacroNotFoundException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function file_put_contents;
use function sys_get_temp_dir;
use function uniqid;

#[CoversClass(ConfigSource::class)]
#[CoversClass(ArrayConfig::class)]
#[CoversClass(JsonConfig::class)]
#[CoversClass(XmlConfig::class)]
final class ConfigSourceTest extends TestCase
{
    private ConfigSource $source;

    protected function setUp(): void
    {
        $this->source = new ConfigSource();
        ConfigSource::resetMacro();
    }

    public function testShouldReturnAnEmptyConfigurationWhenNoSourcesAreAdded(): void
    {
        $this->assertEmpty($this->source->build()->getAll());
    }

    public function testShouldBuildFromAnInMemoryArraySource(): void
    {
        $config = $this->source->fromArray(['app' => ['debug' => true]])->build();

        $this->assertSame(true, $config->get('app.debug'));
    }

    public function testShouldLoadAJsonFileSource(): void
    {
        $content = json_encode(['app' => ['name' => 'Omega']]);
        $this->assertIsString($content);

        $file   = self::writeTempFile($content);
        $config = $this->source->fromJson($file)->build();

        $this->assertSame('Omega', $config->get('app.name'));
    }

    public function testShouldLoadAnXmlFileSource(): void
    {
        $file = self::writeTempFile('<?xml version="1.0"?><app><name>Omega</name></app>');

        $config = $this->source->fromXml($file)->build();

        $this->assertSame('Omega', $config->get('name'));
    }

    public function testShouldThrowWhenAnXmlFileCannotBeRead(): void
    {
        $this->expectException(MalformedXmlException::class);

        $this->source->fromXml(__DIR__ . '/fixtures/config/not-found.xml')->build();
    }

    public function testShouldMergeMultipleSourcesRecursively(): void
    {
        $config = $this->source
            ->fromArray(['nested' => ['key' => 'value']])
            ->fromArray(['nested' => ['other' => 'other_value']])
            ->build();

        $this->assertEquals(['nested' => ['key' => 'value', 'other' => 'other_value']], $config->getAll());
    }

    public function testShouldSupportSectionGrouping(): void
    {
        $config = $this->source
            ->fromArray(['key' => 'value'])
            ->fromArray(['secret' => 'x'], 'secrets')
            ->build();

        $this->assertEquals([
            'key'     => 'value',
            'secrets' => ['secret' => 'x'],
        ], $config->getAll());
    }

    public function testShouldHonourTheMergeStrategyOverride(): void
    {
        $config = $this->source
            ->fromArray(['items' => [1, 2, 3]])
            ->fromArray(['items' => [3, 4, 5]])
            ->build(MergeStrategy::MERGE_INDEXED);

        $this->assertEquals(['items' => [1, 2, 3, 4, 5]], $config->getAll());
    }

    public function testShouldGiveHigherPrioritySourcesPrecedence(): void
    {
        $config = $this->source
            ->fromArray(['debug' => false], null, 10)
            ->fromArray(['debug' => true], null, 50)
            ->build();

        $this->assertSame(true, $config->get('debug'));
    }

    public function testShouldExecuteARegisteredMacro(): void
    {
        $source = new ConfigSource();

        $filter = self::stringKeyedContent(...);
        $fromYaml = function (array $content) use ($source, $filter): ConfigSource {
            return $source->fromArray($filter($content));
        };
        ConfigSource::macro('fromYaml', $fromYaml);

        $yaml = $source->__call('fromYaml', [['app' => ['name' => 'Yaml']]]);
        $this->assertInstanceOf(ConfigSource::class, $yaml);

        $config = $yaml->build();

        $this->assertSame('Yaml', $config->get('app.name'));
    }

    public function testShouldReportRegisteredMacros(): void
    {
        ConfigSource::macro('myMacro', fn () => null);

        $this->assertTrue(ConfigSource::hasMacro('myMacro'));
    }

    public function testShouldThrowWhenAnUnknownMacroIsCalled(): void
    {
        $this->expectException(MacroNotFoundException::class);

        $this->source->__call('doesNotExist', []);
    }

    private static function writeTempFile(string $content): string
    {
        $path = sys_get_temp_dir() . '/omega-config-source-' . uniqid() . '.tmp';
        file_put_contents($path, $content);

        return $path;
    }

    /**
     * Keep only the string-keyed entries of the payload.
     *
     * @param array<mixed> $content The macro payload.
     * @return array<string, mixed> The string-keyed subset of the payload.
     */
    private static function stringKeyedContent(array $content): array
    {
        $result = [];

        foreach ($content as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
