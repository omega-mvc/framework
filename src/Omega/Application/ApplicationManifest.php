<?php

/**
 * Part of Omega - Application Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Application;

use function array_filter;
use function array_map;
use function array_merge;
use function array_reduce;
use function array_values;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function is_array;
use function is_string;
use function json_decode;
use function var_export;
use function Omega\Application\slash;

use const PHP_EOL;

/**
 * ApplicationManifest handles caching and retrieval of package information.
 *
 * This class reads installed Composer packages, extracts relevant configuration
 * data, and caches it to a PHP file for faster access. It provides methods to
 * retrieve service providers and other package-related metadata.
 *
 * @category  Omega
 * @package   Support
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
final class ApplicationManifest
{
    /** @var string $basePath The base path of the application. */
    private readonly string $basePath;

    /** @var string Path where cached package manifest is stored. */
    private readonly string $applicationCachePath;

    /** @var array<mixed>|null Cached package manifest. */
    public ?array $applicationManifest = null;

    /**
     * Constructor for ApplicationManifest.
     *
     * @param string      $basePath             The base path of the application.
     * @param string      $applicationCachePath Path where cached package manifest is stored.
     * @param string|null $vendorPath           Optional vendor path; defaults to '/vendor/composer/'.
     */
    public function __construct(string $basePath, string $applicationCachePath, private ?string $vendorPath = null)
    {
        $this->basePath             = slash($basePath);
        $this->applicationCachePath = slash($applicationCachePath);

        $this->vendorPath = $vendorPath !== null
            ? slash($vendorPath)
            : slash('/vendor/composer/');
    }

    /**
     * Get all registered providers from the cached package manifest.
     *
     * @return string[] List of provider class names.
     */
    public function providers(): array
    {
        return $this->config('providers');
    }

    /**
     * Retrieve an array of values for a given key from the package manifest.
     *
     * @param string $key The key to retrieve from each package configuration.
     * @return string[] Array of non-empty values for the given key.
     */
    private function config(string $key): array
    {
        $flattened = array_reduce(
            array_map(
                fn (mixed $package): array => $this->configValues($package, $key),
                $this->getApplicationManifest()
            ),
            static fn (array $carry, array $item): array => [...$carry, ...$item],
            []
        );

        return array_values(array_filter(
            $flattened,
            static fn (mixed $value): bool => is_string($value) && '' !== $value
        ));
    }

    /**
     * Collect the configured values for a single package.
     *
     * @param mixed  $package The package manifest entry.
     * @param string $key     The configuration key to collect.
     * @return array<mixed> The raw values defined on the package for the given key.
     */
    private function configValues(mixed $package, string $key): array
    {
        if (!is_array($package)) {
            return [];
        }

        if (!isset($package[$key])) {
            return [];
        }

        $entry = $package[$key];

        if (is_array($entry)) {
            return $entry;
        }

        return is_string($entry) ? [$entry] : [];
    }

    /**
     * Get the cached package manifest, building it if it does not exist.
     *
     * @return array<mixed> Cached package manifest.
     */
    private function getApplicationManifest(): array
    {
        if ($this->applicationManifest) {
            return $this->applicationManifest;
        }

        $cacheFile = $this->applicationCachePath . 'packages.php';

        if (false === file_exists($cacheFile)) {
            return $this->applicationManifest = $this->build();
        }

        $manifest = require $cacheFile;

        return $this->applicationManifest = is_array($manifest) ? $manifest : [];
    }

    /**
     * Build the package manifest cache from installed Composer packages.
     *
     * Scans the composer installed.json file and the omega-mvc vendor directory,
     * extracts 'omega-mvc' extra data, and writes a cached PHP file for future
     * access.
     *
     * @return array<mixed> The built package manifest.
     */
    public function build(): array
    {
        $provider = $this->findExternalProvider();

        $file     = $this->basePath . $this->vendorPath . 'installed.json';
        $packages = $this->readPackages($file);

        $installed = array_reduce(
            $packages,
            static function (array $carry, mixed $package): array {
                if (!is_array($package)) {
                    return $carry;
                }

                if (!is_string($package['name'] ?? null)) {
                    return $carry;
                }

                if (!is_array($package['extra'] ?? null)) {
                    return $carry;
                }

                if (!isset($package['extra']['omega-mvc'])) {
                    return $carry;
                }

                $carry[$package['name']] = $package['extra']['omega-mvc'];

                return $carry;
            },
            []
        );

        $result = array_merge($provider, $installed);

        $this->applicationManifest = $result;

        file_put_contents(
            $this->applicationCachePath . 'packages.php',
            '<?php return ' . var_export($result, true) . ';' . PHP_EOL
        );

        return $result;
    }

    /**
     * Scan the vendor directory for external providers exposing 'omega-mvc' extra data.
     *
     * Looks for a composer.json file in every subdirectory of the
     * vendor/omega-mvc directory and collects the 'omega-mvc' extra data
     * defined by each external package.
     *
     * @return array<mixed> The collected package manifest entries.
     */
    private function findExternalProvider(): array
    {
        $provider = array_filter(
            array_map(
                fn (string $file): ?array => $this->resolveExternalPackage($file),
                glob($this->basePath . '/vendor/omega-mvc/*/composer.json') ?: []
            )
        );

        return $provider === [] ? [] : array_merge(...$provider);
    }

    /**
     * Resolve a single external package composer.json file.
     *
     * Returns the 'omega-mvc' extra data keyed by its package name, or null
     * when the file is unreadable, contains invalid JSON, or does not
     * declare a name, an extra array, or an 'omega-mvc' key.
     *
     * @param string $file The path to the composer.json file.
     * @return array<string, mixed>|null The resolved package entry, or null.
     */
    private function resolveExternalPackage(string $file): ?array
    {
        $contents = file_get_contents($file);

        if (false === $contents) {
            return null;
        }

        $composer = json_decode($contents, true);

        if (!is_array($composer)) {
            return null;
        }

        if (!is_string($composer['name'] ?? null)) {
            return null;
        }

        if (!is_array($composer['extra'] ?? null)) {
            return null;
        }

        if (!isset($composer['extra']['omega-mvc'])) {
            return null;
        }

        return [$composer['name'] => $composer['extra']['omega-mvc']];
    }

    /**
     * Read the installed packages from the composer installed.json file.
     *
     * @param string $file The path to the installed.json file.
     * @return array<mixed> The decoded package list, or an empty array.
     */
    private function readPackages(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }

        $contents = file_get_contents($file);

        if (false === $contents) {
            return [];
        }

        $decoded = json_decode($contents, true);

        if (!is_array($decoded)) {
            return [];
        }

        if (!isset($decoded['packages'])) {
            return [];
        }

        if (!is_array($decoded['packages'])) {
            return [];
        }

        return $decoded['packages'];
    }
}
