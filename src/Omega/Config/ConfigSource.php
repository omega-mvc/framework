<?php

/**
 * Part of Omega - Config Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Config;

use Omega\Config\Source\ArrayConfig;
use Omega\Config\Source\JsonConfig;
use Omega\Config\Source\XmlConfig;
use Omega\Macroable\MacroableTrait;

/**
 * Convenient, macro-ready entry point for assembling a configuration repository
 * from multiple sources at runtime.
 *
 * `ConfigSource` wraps {@see ConfigBuilder} and the `Source\` implementations
 * ({@see ArrayConfig}, {@see JsonConfig}, {@see XmlConfig}) behind a small fluent
 * surface, and is macroable so applications can register additional source formats
 * (e.g. YAML, INI) without touching the framework code.
 *
 * ```php
 * $config = (new ConfigSource())
 *     ->fromArray(['debug' => true])
 *     ->fromJson(base_path('config/secrets.json'), 'secrets', 50)
 *     ->fromXml(base_path('config/security.xml'))
 *     ->build();
 * ```
 *
 * Register a custom format:
 *
 * ```php
 * ConfigSource::macro('fromYaml', function (string $file, ?string $section = null): ConfigSource {
 *     return $this->fromArray(yaml_parse_file($file), $section);
 * });
 *
 * $config = ConfigSource::fromYaml('config/deploy.yml')->build();
 * ```
 *
 * @category  Omega
 * @package   Config
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
final class ConfigSource
{
    use ConfigTrait;
    use MacroableTrait;

    /**
     * The underlying builder that accumulates the registered sources.
     *
     * @var ConfigBuilder
     */
    private ConfigBuilder $builder;

    /**
     * Create a new empty config source aggregator.
     */
    public function __construct()
    {
        $this->builder = new ConfigBuilder();
    }

    /**
     * Add an in-memory array source.
     *
     * @param array<string, mixed> $content  The associative array to load.
     * @param string|null          $section  Optional section to group the source under.
     * @param int                  $priority Priority of the source (higher wins).
     * @return ConfigSource The same instance for method chaining.
     */
    public function fromArray(array $content, ?string $section = null, int $priority = 0): self
    {
        $this->builder->addConfiguration(new ArrayConfig($content), $section, $priority);

        return $this;
    }

    /**
     * Add a JSON file source.
     *
     * @param string      $file     Path to the JSON file.
     * @param string|null $section  Optional section to group the source under.
     * @param int         $priority Priority of the source (higher wins).
     * @return ConfigSource The same instance for method chaining.
     */
    public function fromJson(string $file, ?string $section = null, int $priority = 0): self
    {
        $this->builder->addConfiguration(new JsonConfig($file), $section, $priority);

        return $this;
    }

    /**
     * Add an XML file source.
     *
     * @param string      $file     Path to the XML file.
     * @param string|null $section  Optional section to group the source under.
     * @param int         $priority Priority of the source (higher wins).
     * @return ConfigSource The same instance for method chaining.
     */
    public function fromXml(string $file, ?string $section = null, int $priority = 0): self
    {
        $this->builder->addConfiguration(new XmlConfig($file), $section, $priority);

        return $this;
    }

    /**
     * Build the configuration repository from the accumulated sources.
     *
     * @param MergeStrategy|null $strategy The merge strategy to apply (default: REPLACE_INDEXED).
     * @return ConfigRepositoryInterface The constructed configuration repository.
     */
    public function build(?MergeStrategy $strategy = null): ConfigRepositoryInterface
    {
        return $this->builder->build($strategy);
    }
}
