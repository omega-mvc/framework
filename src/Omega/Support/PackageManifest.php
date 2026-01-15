<?php

/**
 * Part of Omega - Facades Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Support;

use function array_filter;
use function array_key_exists;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function var_export;

use const PHP_EOL;

/**
 * PackageManifest handles caching and retrieval of package information.
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
final class PackageManifest
{
    /**
     * @var array<string, array<string, array<int, string>>>|null Cached package manifest.
     */
    public ?array $packageManifest = null;

    /**
     * Constructor for PackageManifest.
     *
     * @param string $basePath The base path of the application.
     * @param string $applicationCachePath Path where cached package manifest is stored.
     * @param string|null $vendorPath Optional vendor path; defaults to '/vendor/composer/'.
     */
    public function __construct(
        private readonly string $basePath,
        private readonly string $applicationCachePath,
        private ?string $vendorPath = null,
    ) {
        $this->vendorPath ??= slash(path: '/vendor/composer/');
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
    protected function config(string $key): array
    {
        $manifest = $this->getPackageManifest();
        $result   = [];

        foreach ($manifest as $configuration) {
            if (array_key_exists($key, $configuration)) {
                $values = (array) $configuration[$key];
                foreach ($values as $value) {
                    if (false === empty($value)) {
                        $result[] = $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Get the cached package manifest, building it if it does not exist.
     *
     * @return array<string, array<string, array<int, string>>> Cached package manifest.
     */
    protected function getPackageManifest(): array
    {
        if ($this->packageManifest) {
            return $this->packageManifest;
        }

        if (false === file_exists($this->applicationCachePath . 'packages.php')) {
            $this->build();
        }

        return $this->packageManifest = require $this->applicationCachePath . 'packages.php';
    }

    /**
     * Build the package manifest cache from installed Composer packages.
     *
     * Scans the composer installed.json file, extracts 'omega-mvc' extra data,
     * and writes a cached PHP file for future access.
     *
     * @return void
     */
    public function build(): void
    {
        $packages = [];
        $provider = [];

        // vendor\composer\installed.json
        if (file_exists($file = $this->basePath . $this->vendorPath . 'installed.json')) {
            $installed = file_get_contents($file);
            $installed = json_decode($installed, true);

            $packages = $installed['packages'] ?? [];
        }

        foreach ($packages as $package) {
            if (isset($package['extra']['omega-mvc'])) {
                $provider[$package['name']] = $package['extra']['omega-mvc'];
            }
        }
        array_filter($provider);

        file_put_contents(
            $this->applicationCachePath
            . 'packages.php',
            '<?php return '
            . var_export($provider, true)
            . ';'
            . PHP_EOL
        );
    }
}
