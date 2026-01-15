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

/** @noinspection PhpSameParameterValueInspection */
/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Support;

use Exception;

use function array_combine;
use function array_diff_key;
use function array_fill;
use function array_fill_keys;
use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_unique;
use function array_values;
use function count;
use function file_exists;
use function file_get_contents;
use function htmlspecialchars;
use function implode;
use function is_bool;
use function is_int;
use function is_null;
use function json_decode;
use function json_last_error;
use function json_last_error_msg;
use function preg_match;
use function rtrim;
use function str_ends_with;

use const ARRAY_FILTER_USE_BOTH;
use const ENT_QUOTES;
use const JSON_ERROR_NONE;

/**
 * Vite class handles asset management and HMR (Hot Module Replacement) integration.
 *
 * This class reads Vite manifest files, generates HTML tags for JS/CSS assets,
 * supports hot module replacement, and caches asset information for performance.
 *
 * @category  Omega
 * @package   Support
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Vite
{
    /** @var string Name of the manifest file. */
    private string $manifestName;

    /** @var int Timestamp of the cached manifest file. */
    private int $cacheTime = 0;

    /** @var array<string, array<string, array<string, string>>> Cached manifest data. */
    public static array $cache = [];

    /** @var string|null HMR (Hot Module Replacement) URL if running HMR server. */
    public static ?string $hot = null;

    /**
     * Constructor.
     *
     * @param string $publicPath Public path of the application.
     * @param string $buildPath Path where Vite build assets are stored.
     */
    public function __construct(
        private readonly string $publicPath,
        private readonly string $buildPath,
    ) {
        $this->manifestName = 'manifest.json';
    }

    /**
     * Render HTML tags for the given Vite entry point(s).
     *
     * This method returns the appropriate `<script>` and `<link>` tags for JS and CSS assets.
     * If HMR (Hot Module Replacement) is active, it includes the HMR client script and uses HMR URLs.
     *
     * @param string ...$entryPoints Entry point filenames defined in Vite manifest.
     * @return string HTML tags for JS/CSS assets.
     * @throws Exception If a manifest file cannot be read or a resource is missing.
     */
    public function __invoke(string ...$entryPoints): string
    {
        if (empty($entryPoints)) {
            return '';
        }

        if ($this->isRunningHRM()) {
            $tags   = [];
            $tags[] = $this->getHmrScript();
            $hmrUrl = $this->getHmrUrl();

            foreach ($entryPoints as $entryPoint) {
                $url    = $hmrUrl . $entryPoint;
                $tags[] = $this->createTag($url, $entryPoint);
            }

            return implode("\n", $tags);
        }

        $imports = $this->getManifestImports($entryPoints);
        $preload = [];
        foreach ($imports['imports'] as $entryPoint) {
            $url       = $this->getManifest($entryPoint);
            $preload[] = $this->createPreloadTag($url);
        }

        foreach ($imports['css'] as $entryPoint) {
            $preload[] = $this->createStyleTag($this->buildPath . $entryPoint);
        }

        $assets    = $this->gets($entryPoints);
        $cssAssets = array_filter(
            $assets,
            fn ($file, $url) => $this->isCssFile($file),
            ARRAY_FILTER_USE_BOTH
        );

        $jsAssets = array_diff_key($assets, $cssAssets);
        $tags     = array_merge(
            $preload,
            array_map(fn ($url) => $this->createStyleTag($url), $cssAssets),
            array_map(fn ($url) => $this->createScriptTag($url), $jsAssets)
        );

        return implode("\n", $tags);
    }

    /**
     * Set a custom manifest filename.
     *
     * @param string $manifestName The manifest file name to use instead of the default.
     * @return $this Fluent interface, returns the current instance.
     */
    public function manifestName(string $manifestName): self
    {
        $this->manifestName = $manifestName;

        return $this;
    }

    /**
     * Flush cached manifest data and HMR URL.
     *
     * This clears the internal cache and resets the HMR URL, forcing reloading of manifest on next access.
     *
     * @return void
     */
    public static function flush(): void
    {
        static::$cache = [];
        static::$hot   = null;
    }

    /**
     * Get the full path to the Vite manifest file.
     *
     * @return string Full path to the manifest file.
     * @throws Exception If the manifest file does not exist.
     */
    public function manifest(): string
    {
        if (file_exists($fileName = "{$this->publicPath}/{$this->buildPath}/{$this->manifestName}")) {
            return $fileName;
        }

        throw new Exception("Manifest file not found {$fileName}");
    }

    /**
     * Load and decode the Vite manifest JSON file.
     *
     * Caches the decoded manifest and reuses it if the manifest file has not changed since last load.
     *
     * @return array<string, array<string, string|string[]>> Decoded manifest data.
     * @throws Exception If the manifest file cannot be read or JSON decoding fails.
     */
    public function loader(): array
    {
        $fileName    = $this->manifest();
        $currentTime = $this->manifestTime();

        if (
            array_key_exists($fileName, static::$cache)
            && $this->cacheTime === $currentTime
        ) {
            return static::$cache[$fileName];
        }

        $this->cacheTime = $currentTime;
        $load            = file_get_contents($fileName);

        if ($load === false) {
            throw new Exception("Failed to read manifest file: {$fileName}");
        }

        $json = json_decode($load, true);

        if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Manifest JSON decode error: ' . json_last_error_msg());
        }

        return static::$cache[$fileName] = $json;
    }

    /**
     * Get the built path for a single resource from the Vite manifest.
     *
     * @param string $resourceName The resource name as defined in the manifest.
     * @return string Full URL/path to the resource file.
     * @throws Exception If the resource is not found in the manifest.
     */
    public function getManifest(string $resourceName): string
    {
        $asset = $this->loader();

        if (!array_key_exists($resourceName, $asset)) {
            throw new Exception("Resource file not found {$resourceName}");
        }

        return $this->buildPath . $asset[$resourceName]['file'];
    }

    /**
     * Get the built paths for multiple resources from the Vite manifest.
     *
     * @param string[] $resourceNames List of resource names defined in the manifest.
     * @return array<string, string> Array of resource name => path mappings.
     * @throws Exception If the manifest cannot be loaded.
     * @deprecated Since v0.40. Use `gets()` instead.
     */
    public function getsManifest(array $resourceNames): array
    {
        $asset = $this->loader();

        $resources = [];
        foreach ($resourceNames as $resource) {
            if (array_key_exists($resource, $asset)) {
                $resources[$resource] = $this->buildPath . $asset[$resource]['file'];
            }
        }

        return $resources;
    }

    /**
     * Collect imports and CSS files for the given resources.
     *
     * This builds an array with 'imports' and 'css' for the requested resources,
     * resolving nested imports recursively.
     *
     * @param string[] $resources List of resource names.
     * @return array{imports: string[], css: string[]} Arrays of import and CSS file paths.
     * @throws Exception If the manifest cannot be loaded.
     */
    public function getManifestImports(array $resources): array
    {
        $assets      = $this->loader();
        $resourceSet = array_fill_keys($resources, true);

        $preload = ['imports' => [], 'css' => []];

        foreach ($assets as $name => $asset) {
            if (isset($resourceSet[$name])) {
                $this->collectImports($assets, $asset, $preload);
            }
        }

        $preload['imports'] = array_values(array_unique($preload['imports']));
        $preload['css']     = array_values(array_unique($preload['css']));

        return $preload;
    }

    /**
     * Recursively collect CSS and JS import dependencies for a single asset.
     *
     * @param array<string, array<string, string|string[]>> $assets Full manifest assets array.
     * @param array<string, string|string[]> $asset Asset entry to collect dependencies from.
     * @param array{imports: string[], css: string[]} $preload Reference to the preload array to populate.
     * @return void
     */
    private function collectImports(array $assets, array $asset, array &$preload): void
    {
        if (false === empty($asset['css'])) {
            $preload['css'] = array_merge($preload['css'], $asset['css']);
        }

        if (false === empty($asset['imports'])) {
            foreach ($asset['imports'] as $import) {
                $preload['imports'][] = $import;

                if (isset($assets[$import])) {
                    $this->collectImports($assets, $assets[$import], $preload);
                }
            }
        }
    }

    /**
     * Get the URL for a single resource, using HMR if running.
     *
     * @param string $resourceName Resource name to retrieve.
     * @return string URL to the resource file.
     * @throws Exception If manifest or hot file cannot be loaded.
     */
    public function get(string $resourceName): string
    {
        if (!$this->isRunningHRM()) {
            return $this->getManifest($resourceName);
        }

        $hot = $this->getHmrUrl();

        return $hot . $resourceName;
    }

    /**
     * Get the URLs for multiple resources, using HMR if running.
     *
     * @param string[] $resourceNames List of resource names.
     * @return array<string, string> Mapping of resource name => URL.
     * @throws Exception If manifest or hot file cannot be loaded.
     */
    public function gets(array $resourceNames): array
    {
        if (false === $this->isRunningHRM()) {
            $asset     = $this->loader();
            $resources = [];

            foreach ($resourceNames as $resource) {
                if (array_key_exists($resource, $asset)) {
                    $resources[$resource] = $this->buildPath . $asset[$resource]['file'];
                }
            }

            return $resources;
        }

        $hot  = $this->getHmrUrl();

        return array_combine(
            $resourceNames,
            array_map(fn ($asset) => $hot . $asset, $resourceNames)
        );
    }

    /**
     * Determine if the HMR (Hot Module Replacement) server is currently running.
     *
     * @return bool True if HMR is running, false otherwise.
     */
    public function isRunningHRM(): bool
    {
        return is_file("{$this->publicPath}/hot");
    }

    /**
     * Get the base URL of the HMR server.
     *
     * @return string HMR server URL with trailing slash.
     * @throws Exception If the hot file cannot be read.
     */
    public function getHmrUrl(): string
    {
        if (!is_null(static::$hot)) {
            return static::$hot;
        }

        $hotFile = "{$this->publicPath}/hot";
        $hot     = file_get_contents($hotFile);

        if ($hot === false) {
            throw new Exception("Failed to read hot file: {$hotFile}");
        }

        $hot  = rtrim($hot);
        $dash = str_ends_with($hot, '/') ? '' : '/';

        return static::$hot = $hot . $dash;
    }

    /**
     * Get the HMR client script tag.
     *
     * @return string Script tag for HMR client.
     * @throws Exception If HMR URL cannot be determined.
     */
    public function getHmrScript(): string
    {
        return '<script type="module" src="' . $this->getHmrUrl() . '@vite/client"></script>';
    }

    /**
     * Get the last cached manifest timestamp.
     *
     * @return int Timestamp of the last loaded manifest.
     */
    public function cacheTime(): int
    {
        return $this->cacheTime;
    }

    /**
     * Get the last modification time of the manifest file.
     *
     * @return int Timestamp of the manifest file.
     * @throws Exception If the manifest file cannot be found.
     */
    public function manifestTime(): int
    {
        return filemtime($this->manifest());
    }

    /**
     * Generate preload link tags for given entry points.
     *
     * @param string[] $entryPoints List of entry point resource names.
     * @return string HTML string containing preload tags.
     * @throws Exception If manifest files cannot be read.
     */
    public function getPreloadTags(array $entryPoints): string
    {
        if ($this->isRunningHRM()) {
            return '';
        }

        $tags    = [];
        $imports = $this->getManifestImports($entryPoints);

        foreach ($imports['imports'] as $entryPoint) {
            $url    = $this->getManifest($entryPoint);
            $tags[] = $this->createPreloadTag($url);
        }

        foreach ($imports['css'] as $entryPoint) {
            $tags[] = $this->createStyleTag($this->buildPath . $entryPoint);
        }

        return implode("\n", $tags);
    }

    /**
     * Generate script and style tags for the given entry points with optional attributes.
     *
     * @param string[] $entryPoints List of entry point resource names.
     * @param array<string|int, string|bool|int|null>|null $attributes Optional HTML attributes.
     * @return string HTML string containing script and style tags.
     * @throws Exception If manifest files cannot be read.
     */
    public function getTags(array $entryPoints, ?array $attributes = null): string
    {
        return $this->getCustomTags(
            array_combine($entryPoints, array_fill(0, count($entryPoints), $attributes ?? []))
        );
    }

    /**
     * Generate custom script and style tags for multiple entry points with per-entry attributes.
     *
     * @param array<string, array<string|int, string|bool|int|null>> $entryPoints Entry points and attributes.
     * @param array<string|int, string|bool|int|null> $defaultAttributes Default attributes applied if not
     *                      provided per entry.
     * @return string HTML string containing custom tags.
     * @throws Exception If manifest files cannot be read.
     */
    public function getCustomTags(array $entryPoints, array $defaultAttributes = []): string
    {
        $tags = [];

        if ($this->isRunningHRM()) {
            $tags[] = $this->getHmrScript();
        }

        $assets    = $this->gets(array_keys($entryPoints));
        $cssAssets = array_filter(
            $assets,
            fn ($file, $url) => $this->isCssFile($file),
            ARRAY_FILTER_USE_BOTH
        );

        $jsAssets = array_diff_key($assets, $cssAssets);
        $tags     = array_merge(
            array_map(
                fn ($url, $file) => $this->createStyleTag($url, $entryPoints[$file] ?? $defaultAttributes),
                array_values($cssAssets),
                array_keys($cssAssets)
            ),
            array_map(
                fn ($url, $file) => $this->createScriptTag($url, $entryPoints[$file] ?? $defaultAttributes),
                array_values($jsAssets),
                array_keys($jsAssets)
            )
        );

        return implode("\n", $tags);
    }

    /**
     * Create a single tag for a resource, choosing script or style based on file type.
     *
     * @param string $url Resource URL.
     * @param string $entryPoint Resource entry name.
     * @param array<string|int, string|bool|int|null>|null $attributes Optional HTML attributes.
     * @return string HTML tag string.
     */
    private function createTag(string $url, string $entryPoint, ?array $attributes = null): string
    {
        if ($this->isCssFile($entryPoint)) {
            return $this->createStyleTag($url);
        }

        return $this->createScriptTag($url, $attributes);
    }

    /**
     * Create a script tag with optional attributes.
     *
     * @param string $url Script URL.
     * @param array<string|int, string|bool|int|null>|null $attributes Optional HTML attributes.
     * @return string HTML script tag.
     */
    private function createScriptTag(string $url, ?array $attributes = null): string
    {
        $attributes ??= [];

        if (false === isset($attributes['type'])) {
            $attributes = array_merge(['type' => 'module'], $attributes);
        }

        $attributes['src'] = $this->escapeUrl($url);
        $attributes        = $this->buildAttributeString($attributes);

        return "<script {$attributes}></script>";
    }

    /**
     * Create a style (link) tag with optional attributes.
     *
     * @param string $url CSS file URL.
     * @param array<string|int, string|bool|int|null>|null $attributes Optional HTML attributes.
     * @return string HTML link tag.
     */
    private function createStyleTag(string $url, ?array $attributes = null): string
    {
        if ($this->isRunningHRM()) {
            return $this->createScriptTag($url, $attributes);
        }

        $attributes ??= [];
        $attributes['rel']  = 'stylesheet';
        $attributes['href'] = $this->escapeUrl($url);
        $attributes         = $this->buildAttributeString($attributes);

        return "<link {$attributes}>";
    }

    /**
     * Create a preload link tag for a given resource URL.
     *
     * @param string $url Resource URL to preload.
     * @return string HTML preload link tag.
     */
    private function createPreloadTag(string $url): string
    {
        $attributes = $this->buildAttributeString([
            'rel'  => 'modulepreload',
            'href' => $this->escapeUrl($url),
        ]);

        return "<link {$attributes}>";
    }

    // helper functions

    /**
     * Determine if a filename is a CSS-related file.
     *
     * @param string $filename File name to check.
     * @return bool True if CSS or preprocessor file, false otherwise.
     */
    private function isCssFile(string $filename): bool
    {
        return preg_match('/\.(css|less|sass|scss|styl|stylus|pcss|postcss)$/', $filename) === 1;
    }

    /**
     * Escape a URL for safe inclusion in HTML attributes.
     *
     * @param string $url URL to escape.
     * @return string Escaped URL.
     */
    private function escapeUrl(string $url): string
    {
        return htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Build an HTML attribute string from an associative array.
     *
     * @param array<string|int, string|bool|int|null> $attributes Attributes to convert.
     * @return string HTML-ready attribute string.
     */
    private function buildAttributeString(array $attributes): string
    {
        if (empty($attributes)) {
            return '';
        }

        $parts = [];
        foreach ($attributes as $key => $value) {
            if (is_int($key)) {
                $parts[] = htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
                continue;
            }

            $key = htmlspecialchars($key, ENT_QUOTES, 'UTF-8');

            $part = match (true) {
                is_bool($value) => $value ? $key : null,
                $value === null => null,
                default         => $key . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"',
            };

            if ($part !== null) {
                $parts[] = $part;
            }
        }

        return implode(' ', $parts);
    }
}
