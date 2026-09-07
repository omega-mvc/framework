<?php

declare(strict_types=1);

namespace Tests\Config;

use Omega\Config\ConfigSource;
use Omega\Config\MergeStrategy;
use Omega\Config\Source\ArrayConfig;
use Omega\Config\Source\JsonConfig;
use Omega\Config\Source\XmlConfig;
use Omega\Macroable\Exceptions\MacroNotFoundException;

use function file_put_contents;
use function sys_get_temp_dir;
use function uniqid;

covers(ConfigSource::class);
covers(ArrayConfig::class);
covers(JsonConfig::class);
covers(XmlConfig::class);

beforeEach(function (): void {
    $this->source = new ConfigSource();
    ConfigSource::resetMacro();
});

it('should return an empty configuration when no sources are added', function (): void {
    expect($this->source->build()->getAll())->toBeEmpty();
});

it('should build from an in-memory array source', function (): void {
    $config = $this->source->fromArray(['app' => ['debug' => true]])->build();

    expect($config->get('app.debug'))->toBe(true);
});

it('should load a JSON file source', function (): void {
    $content = json_encode(['app' => ['name' => 'Omega']]);
    $this->assertIsString($content);

    $file   = writeTempFile($content);
    $config = $this->source->fromJson($file)->build();

    expect($config->get('app.name'))->toBe('Omega');
});

it('should load an XML file source', function (): void {
    $file = writeTempFile('<?xml version="1.0"?><app><name>Omega</name></app>');

    $config = $this->source->fromXml($file)->build();

    expect($config->get('name'))->toBe('Omega');
});

it('should merge multiple sources recursively', function (): void {
    $config = $this->source
        ->fromArray(['nested' => ['key' => 'value']])
        ->fromArray(['nested' => ['other' => 'other_value']])
        ->build();

    expect($config->getAll())->toEqual(['nested' => ['key' => 'value', 'other' => 'other_value']]);
});

it('should support section grouping', function (): void {
    $config = $this->source
        ->fromArray(['key' => 'value'])
        ->fromArray(['secret' => 'x'], 'secrets')
        ->build();

    expect($config->getAll())->toEqual([
        'key'     => 'value',
        'secrets' => ['secret' => 'x'],
    ]);
});

it('should honour the merge strategy override', function (): void {
    $config = $this->source
        ->fromArray(['items' => [1, 2, 3]])
        ->fromArray(['items' => [3, 4, 5]])
        ->build(MergeStrategy::MERGE_INDEXED);

    expect($config->getAll())->toEqual(['items' => [1, 2, 3, 4, 5]]);
});

it('should give higher priority sources precedence', function (): void {
    $config = $this->source
        ->fromArray(['debug' => false], null, 10)
        ->fromArray(['debug' => true], null, 50)
        ->build();

    expect($config->get('debug'))->toBe(true);
});

it('should execute a registered macro', function (): void {
    $source = new ConfigSource();

    $fromYaml = function (array $content) use ($source): ConfigSource {
        return $source->fromArray(stringKeyedContent($content));
    };
    ConfigSource::macro('fromYaml', $fromYaml);

    $yaml = $source->__call('fromYaml', [['app' => ['name' => 'Yaml']]]);
    $this->assertInstanceOf(ConfigSource::class, $yaml);

    $config = $yaml->build();

    expect($config->get('app.name'))->toBe('Yaml');
});

it('should report registered macros', function (): void {
    ConfigSource::macro('myMacro', fn () => null);

    expect(ConfigSource::hasMacro('myMacro'))->toBeTrue();
});

it('should throw when an unknown macro is called', function (): void {
    $this->source->__call('doesNotExist', []);
})->throws(MacroNotFoundException::class);

function writeTempFile(string $content): string
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
function stringKeyedContent(array $content): array
{
    $result = [];

    foreach ($content as $key => $value) {
        if (is_string($key)) {
            $result[$key] = $value;
        }
    }

    return $result;
}
