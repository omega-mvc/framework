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

use function array_key_exists;
use function array_reduce;
use function count;
use function explode;
use function is_array;
use function reset;

/**
 * Abstract implementation of the configuration repository.
 *
 * This class provides a base implementation for managing configuration settings,
 * handling operations such as retrieval, modification, and merging. Concrete
 * implementations should extend this class to provide specific behavior.
 *
 * @category  Omega
 * @package   Config
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
abstract class AbstractConfigRepository implements ConfigRepositoryInterface
{
    use ConfigTrait;

    /** @var array<string, mixed> The store holding all configuration variables. */
    protected array $config = [];

    /**
     * Create new config using array.
     *
     * @param array<string, mixed> $config The store holding all configuration variables.
     * @return void
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, mixed> The entire configuration data as an associative array.
     */
    public function getAll(): array
    {
        return $this->config;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $key): bool
    {
        /** @var array{0: bool, 1: mixed} $state */
        $state = array_reduce(
            explode('.', $key),
            /**
             * @param array{0: bool, 1: mixed} $state
             * @return array{0: bool, 1: mixed}
             */
            static function (array $state, string $section): array {
                if (!$state[0]) {
                    return $state;
                }

                $config = $state[1];

                if (!is_array($config) || !array_key_exists($section, $config)) {
                    return [false, null];
                }

                return [true, $config[$section]];
            },
            [true, $this->config]
        );

        return $state[0];
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $key, mixed $default = null): mixed
    {
        /** @var array{0: bool, 1: mixed} $state */
        $state = array_reduce(
            explode('.', $key),
            /**
             * @param array{0: bool, 1: mixed} $state
             * @return array{0: bool, 1: mixed}
             */
            static function (array $state, string $section): array {
                if (!$state[0]) {
                    return $state;
                }

                $config = $state[1];

                if (!is_array($config) || !array_key_exists($section, $config)) {
                    return [false, null];
                }

                return [true, $config[$section]];
            },
            [true, $this->config]
        );

        return $state[0] ? $state[1] : $default;
    }

    /**
     * {@inheritdoc}
     */
    public function set(string $key, mixed $value): void
    {
        $config = &$this->config;

        foreach (explode('.', $key) as $section) {
            (static function (mixed &$config): void {
                if (!is_array($config)) {
                    $config = [];
                }
            })($config);

            /** @var array<string, mixed> $config */
            $arrayConfig = &$config;
            $config      = &$arrayConfig[$section];
        }

        $config = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $key): void
    {
        $config   = &$this->config;
        $sections = explode('.', $key);
        $last     = count($sections) - 1;

        foreach ($sections as $i => $section) {
            $exists = (static function (mixed $config, string $section): bool {
                return is_array($config) && array_key_exists($section, $config);
            })($config, $section);

            if (!$exists) {
                return;
            }

            /** @var array<string, mixed> $config */
            $arrayConfig = &$config;

            if ($i === $last) {
                unset($arrayConfig[$section]);

                return;
            }

            $config = &$arrayConfig[$section];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function clear(): void
    {
        $this->config = [];
    }

    /**
     * {@inheritdoc}
     */
    public function push(string $key, mixed $value): void
    {
        $current = $this->config[$key] ?? null;

        if (!is_array($current)) {
            $current = [];
        }

        $current[] = $value;
        $this->config[$key] = $current;
    }
}
