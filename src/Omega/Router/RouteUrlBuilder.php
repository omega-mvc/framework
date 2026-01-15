<?php

/**
 * Part of Omega - Router Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Router;

use InvalidArgumentException;

use function array_is_list;
use function array_merge;
use function is_string;
use function preg_match;
use function preg_match_all;
use function preg_quote;
use function preg_replace;
use function sprintf;
use function str_contains;
use function trim;

/**
 * Class RouteUrlBuilder
 *
 * Builds URLs from routes and parameters, validating them against
 * defined patterns. Supports both named and positional parameters.
 *
 * @category  Omega
 * @package   Router
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class RouteUrlBuilder
{
    /**
     * RouteUrlBuilder constructor.
     *
     * @param array<string, string> $patterns Optional custom patterns for placeholders.
     * @return void
     * @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection
     */
    public function __construct(private array $patterns = [])
    {
    }

    /**
     * Build a URL from a Route and parameters.
     *
     * @param Route                              $route      Route object containing URI and patterns.
     * @param array<string|int, string|int|bool> $parameters Associative or indexed parameters to fill
     *                                                       in the route placeholders.
     * @return string Final URL with parameters replaced.
     */
    public function buildUrl(Route $route, array $parameters): string
    {
        $url           = $route['uri'];
        $patternMap    = $this->patterns + ($route['patterns'] ?? []);
        $isAssociative = !array_is_list($parameters);

        $url = $this->processNamedParameters($url, $parameters, $patternMap, $isAssociative);
        $url = $this->processPatternPlaceholders($url, $parameters, $patternMap, $isAssociative);
        $this->validateAllParametersProcessed($url, $patternMap);

        return $url;
    }

    /**
     * Add or merge additional patterns for URL building.
     *
     * @param array<string, string> $patterns Associative array of pattern => regex.
     * @return void
     */
    public function addPatterns(array $patterns): void
    {
        $this->patterns = array_merge($this->patterns, $patterns);
    }

    /**
     * Get the currently registered patterns.
     *
     * @return array<string, string> Array of pattern => regex.
     */
    public function getPatterns(): array
    {
        return $this->patterns;
    }

    /**
     * Process named parameters in the URL.
     *
     * @param string                           $url           URL containing placeholders.
     * @param array<string|int, string|int|bool> $parameters     Parameters to replace in the URL.
     * @param array<string, string>            $patternMap    Map of patterns to regex.
     * @param bool                             $isAssociative True if parameters are associative, false if indexed.
     * @return string URL with named parameters replaced.
     */
    private function processNamedParameters(
        string $url,
        array $parameters,
        array $patternMap,
        bool $isAssociative
    ): string {
        $paramIndex = 0;

        return preg_replace_callback(
            '/\(([^:)]+):([^)]+)\)/',
            function ($matches) use ($parameters, $isAssociative, &$paramIndex, $patternMap) {
                $paramName   = $matches[1];
                $patternType = $matches[2];
                $patternKey  = "(:{$patternType})";

                $this->validatePatternExists($patternKey, $patternMap);

                $value = $this->extractParameterValue($parameters, $paramName, $paramIndex, $isAssociative);

                if (false === $isAssociative) {
                    $paramIndex++;
                }

                $this->validateParameterAgainstPattern($value, $paramName, $patternKey, $patternMap[$patternKey]);

                return (string) $value;
            },
            $url
        );
    }

    /**
     * Process generic pattern placeholders in the URL.
     *
     * @param string                             $url           URL containing placeholders.
     * @param array<string|int, string|int|bool> $parameters    Parameters to replace.
     * @param array<string, string>              $patternMap    Map of pattern => regex.
     * @param bool                               $isAssociative True if parameters are associative.
     * @return string URL with patterns replaced.
     */
    private function processPatternPlaceholders(
        string $url,
        array $parameters,
        array $patternMap,
        bool $isAssociative
    ): string {
        $paramIndex = $isAssociative ? 0 : $this->countProcessedParameters($url);

        foreach ($patternMap as $pattern => $regex) {
            while (str_contains($url, $pattern)) {
                $value = $this->getNextParameterValue($parameters, $pattern, $paramIndex, $isAssociative);

                $this->validateParameterAgainstPattern($value, $value, $pattern, $regex);

                $url = preg_replace('/' . preg_quote($pattern, '/') . '/', (string) $value, $url, 1);
                $paramIndex++;
            }
        }

        return $url;
    }

    /**
     * Validate that a given pattern key exists in the pattern map.
     *
     * @param string                 $patternKey Pattern key to check.
     * @param array<string, string>  $patternMap Map of patterns.
     * @return void
     * @throws InvalidArgumentException
     */
    private function validatePatternExists(string $patternKey, array $patternMap): void
    {
        if (false === isset($patternMap[$patternKey])) {
            throw new InvalidArgumentException("Unknown pattern type: {$patternKey}");
        }
    }

    /**
     * Extract a parameter value from the parameters array.
     *
     * @param array<string|int, string|int|bool> $parameters Array of parameters.
     * @param string                             $paramName  Name of the parameter to extract.
     * @param int                                $paramIndex Index to use if parameters are numeric.
     * @param bool                               $isAssociative True if parameters are associative.
     * @return string|int|bool Value of the parameter.
     * @throws InvalidArgumentException
     */
    private function extractParameterValue(
        array $parameters,
        string $paramName,
        int $paramIndex,
        bool $isAssociative
    ): string|int|bool {
        if ($isAssociative) {
            if (false === isset($parameters[$paramName])) {
                throw new InvalidArgumentException("Missing named parameter: {$paramName}");
            }

            return $parameters[$paramName];
        }

        if (false === isset($parameters[$paramIndex])) {
            throw new InvalidArgumentException(
                "Missing parameter at index {$paramIndex} for named parameter {$paramName}"
            );
        }

        return $parameters[$paramIndex];
    }

    /**
     * Get the next parameter value for pattern replacement.
     *
     * @param array<string|int, string|int|bool> $parameters Array of parameters.
     * @param string                             $pattern     Pattern placeholder.
     * @param int                                $paramIndex  Index to use for numeric parameters.
     * @param bool                               $isAssociative True if parameters are associative.
     * @return string|int|bool Parameter value to replace pattern.
     * @throws InvalidArgumentException
     */
    private function getNextParameterValue(
        array $parameters,
        string $pattern,
        int $paramIndex,
        bool $isAssociative
    ): string|int|bool {
        if ($isAssociative) {
            $patternName = trim($pattern, '(:)');

            return match (true) {
                isset($parameters[$patternName]) => $parameters[$patternName],
                isset($parameters[$paramIndex])  => $parameters[$paramIndex],
                default                          => throw new InvalidArgumentException(
                    sprintf(
                        "Missing parameter for pattern {%s}. Provide either numeric index {%s} or key '{%s}'",
                        $pattern,
                        $paramIndex,
                        $patternName
                    )
                ),
            };
        }

        if (false === isset($parameters[$paramIndex])) {
            throw new InvalidArgumentException("Missing parameter at index {$paramIndex} for pattern {$pattern}");
        }

        return $parameters[$paramIndex];
    }

    /**
     * Validate that a parameter value matches its regex pattern.
     *
     * @param mixed           $value      Parameter value.
     * @param string|int      $identifier Parameter name or index for error messages.
     * @param string          $pattern    Pattern placeholder.
     * @param string          $regex      Regex to validate against.
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateParameterAgainstPattern(
        mixed $value,
        string|int $identifier,
        string $pattern,
        string $regex
    ): void {
        $stringValue = (string) $value;

        if (1 !== preg_match("/^{$regex}$/", $stringValue)) {
            $errorMsg = is_string($identifier) && $identifier !== $value
                ? "Named parameter '{$identifier}' with value '{$value}' doesn't match pattern {$pattern} ({$regex})"
                : "Parameter '{$value}' doesn't match pattern {$pattern} ({$regex})";

            throw new InvalidArgumentException($errorMsg);
        }
    }

    /**
     * Count how many parameters have been processed in a URL.
     *
     * @param string $originalUrl Original URL with placeholders.
     * @return int Number of processed parameters.
     */
    private function countProcessedParameters(string $originalUrl): int
    {
        return preg_match_all('/\([^:)]+:[^)]+\)/', $originalUrl);
    }

    /**
     * Ensure all named parameters and patterns have been processed in the URL.
     *
     * @param string                $url        Final URL.
     * @param array<string, string> $patternMap Pattern map to validate.
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateAllParametersProcessed(string $url, array $patternMap): void
    {
        if (preg_match('/\([^)]+:[^)]+\)/', $url)) {
            throw new InvalidArgumentException('Some named parameters were not replaced in URL');
        }

        foreach ($patternMap as $pattern => $regex) {
            if (str_contains($url, $pattern)) {
                throw new InvalidArgumentException(
                    "Not enough parameters provided. Pattern {$pattern} still exists in URL"
                );
            }
        }
    }
}
