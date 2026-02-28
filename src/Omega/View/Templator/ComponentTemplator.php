<?php

/**
 * Part of Omega - View Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\View\Templator;

use Exception;
use Omega\View\AbstractTemplatorParse;
use Omega\View\DependencyTemplatorInterface;
use Omega\View\Exceptions\ViewFileNotFoundException;
use Omega\View\Exceptions\YeldSectionNotFoundException;
use Omega\View\InteractWithCacheTrait;

use function array_key_exists;
use function class_exists;
use function explode;
use function preg_replace_callback;
use function str_contains;
use function trim;

/**
 * ComponentTemplator is responsible for parsing and rendering template components.
 *
 * This class extends AbstractTemplatorParse and implements DependencyTemplatorInterface.
 * It handles `{% component(...) %}` directives, supports nested components, caching of file contents,
 * namespace management, and dependency tracking for components used in templates.
 *
 * @category   Omega
 * @package    View
 * @subpackage Templator
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class ComponentTemplator extends AbstractTemplatorParse implements DependencyTemplatorInterface
{
    use InteractWithCacheTrait;

    /** @var array<string, string> Cached file contents to avoid multiple file reads. */
    private static array $cache = [];

    /** @var string Holds the namespace for component classes. */
    private string $namespace = '';

    /** @var array<string, int> Tracks component dependencies for the current template. */
    private array $dependOn = [];

    /**
     * {@inheritdoc}
     */
    public function dependOn(): array
    {
        return $this->dependOn;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception When a parsing error occurs.
     */
    public function parse(string $template): string
    {
        self::$cache = [];

        return $this->parseComponent($template);
    }

    /**
     * Sets the namespace to use for component classes.
     *
     * @param string $namespace The namespace prefix for component classes.
     * @return $this Returns the current instance for method chaining.
     */
    public function setNamespace(string $namespace): self
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * Recursively parses component directives within a template.
     *
     * Supports nested components, inner content replacement, and dependency tracking.
     *
     * @param string $template The template content containing `{% component %}` directives.
     * @return string The template with all components resolved.
     * @throws YeldSectionNotFoundException If a yield section referenced in a component is not found.
     * @throws ViewFileNotFoundException If the template cannot be found in any registered path.
     */
    private function parseComponent(string $template): string
    {
        return preg_replace_callback(
            '/{%\s*component\(\s*(.*?)\)\s*%}(.*?){%\s*endcomponent\s*%}/s',
            function ($matches) use ($template) {
                /**if (!array_key_exists(1, $matches)) {
                    return $template; // @codeCoverageIgnore - Death code, removed in next version.
                }
                if (!array_key_exists(2, $matches)) {
                    return $template; // @codeCoverageIgnore - Death code, removed in next version.
                }*/

                $rawParams                = trim($matches[1]);
                [$componentName, $params] = $this->extractComponentAndParams($rawParams);
                $innerContent             = $matches[2];

                if (class_exists($class = $this->namespace . $componentName)) {
                    $component = new $class(...$params);

                    return $component->render($innerContent);
                }

                /**if (false === $this->finder->exists($componentName)) {
                    throw new ViewFileNotFoundException($componentName);
                }*/

                $templatePath = $this->finder->find($componentName);
                $layout       = $this->getContents($templatePath);
                $content      = $this->parseComponent($layout);
                // add parent dependency
                $this->dependOn[$templatePath] = 1;

                return preg_replace_callback(
                    "/{%\s*yield\(\'([^\']+)\'\)\s*%}/",
                    function ($yieldMatches) use ($componentName, $innerContent, $params) {
                        if ($componentName === $yieldMatches[1]) {
                            return $innerContent;
                        }

                        if (array_key_exists($yieldMatches[1], $params)) {
                            return $params[$yieldMatches[1]];
                        }

                        throw new YeldSectionNotFoundException($yieldMatches[1]);
                    },
                    $content
                );
            },
            $template
        );
    }

    /**
     * Extracts the component name and parameters from a raw string.
     *
     * Converts a raw parameter string like `'MyComponent', key: 'value'` into
     * a component name and an associative array of parameters.
     *
     * @param string $rawParams Raw parameter string from the component directive.
     * @return array{0: string, 1: array<string, string>} An array containing the component name
     *         at index 0 and parameters array at index 1.
     */
    private function extractComponentAndParams(string $rawParams): array
    {
        $parts         = explode(',', $rawParams, 2);
        $componentName = trim($parts[0], "'\"");

        $paramsString = $parts[1] ?? '';
        $paramsArray  = array_filter(array_map('trim', explode(',', $paramsString)));

        $params = array_reduce(
            $paramsArray,
            function (array $carry, string $param) {
                if (str_contains($param, ':')) {
                    [$key, $value] = explode(':', $param, 2);
                    $carry[$key] = trim($value, "'\" ");
                } else {
                    $carry[] = trim($param, "'\" ");
                }
                return $carry;
            },
            []
        );

        return [$componentName, $params];
    }
}
