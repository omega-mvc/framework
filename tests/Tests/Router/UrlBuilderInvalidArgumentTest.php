<?php

/**
 * Part of Omega - Tests\Router Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Router;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Omega\Router\Route;
use Omega\Router\RouteUrlBuilder;

/**
 * Class UrlBuilderInvalidArgumentTest
 *
 * This test suite ensures that the RouteUrlBuilder correctly throws
 * InvalidArgumentException when invalid arguments are provided to build URLs.
 * It covers various edge cases, such as unknown pattern types, missing parameters,
 * and parameter values that do not match the expected regex patterns.
 *
 * @category  Tests
 * @package   Router
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Route::class)]
#[CoversClass(RouteUrlBuilder::class)]
class UrlBuilderInvalidArgumentTest extends TestCase
{
    /**
     * Provides a set of invalid argument cases for URL building.
     *
     * Each case is an array containing:
     *  1. 'route'   - An array representing the route definition, including:
     *       - 'uri'      : The route URI, possibly containing placeholders.
     *       - 'patterns' : Any custom patterns for placeholders.
     *  2. 'params'  - An array of parameters passed to the URL builder.
     *       - Can be associative (named parameters) or indexed (positional parameters).
     *  3. 'message' - The expected exception message when the builder fails.
     *
     * @return array<string, array{0: array{uri: string, patterns: array}, 1: array, 2: string}>
     *         Returns an associative array of test cases keyed by a descriptive name.
     */
    public static function invalidArgumentCases(): array
    {
        return [
            // 1. Unknown pattern type
            'Unknown pattern type' => [
                [
                    'uri'      => '/user/(id:unknown)',
                    'patterns' => [],
                ],
                ['id' => 1],
                'Unknown pattern type: (:unknown)',
            ],

            // 2. Missing named parameter (assoc)
            'Missing named parameter (assoc)' => [
                [
                    'uri'      => '/user/(id:num)',
                    'patterns' => [],
                ],
                ['slug' => 123],
                'Missing named parameter: id',
            ],

            // 3. Missing named parameter (indexed)
            'Missing named parameter (indexed)' => [
                [
                    'uri'      => '/user/(id:num)',
                    'patterns' => [],
                ],
                [],
                'Missing parameter at index 0 for named parameter id',
            ],

            // 4. Named parameter value not match regex (assoc)
            'Named parameter value not match regex' => [
                [
                    'uri'      => '/user/(id:num)',
                    'patterns' => [],
                ],
                ['id' => 'abc'],
                "Named parameter 'id' with value 'abc' doesn't match pattern (:num) (\d+)",
            ],

            // 5. Missing parameter for pattern (assoc)
            'Missing parameter for pattern (assoc)' => [
                [
                    'uri'      => '/user/(:num)',
                    'patterns' => [],
                ],
                ['foo' => 'bar'],
                "Missing parameter for pattern {(:num)}. Provide either numeric index {0} or key '{num}'",
            ],

            // 6. Missing parameter for pattern (indexed)
            'Missing parameter for pattern (indexed)' => [
                [
                    'uri'      => '/user/(:num)',
                    'patterns' => [],
                ],
                [],
                'Missing parameter at index 0 for pattern (:num)',
            ],

            // 7. Parameter not match regex for pattern (indexed)
            'Parameter not match regex for pattern' => [
                [
                    'uri'      => '/user/(:num)',
                    'patterns' => [],
                ],
                ['abc'],
                "Parameter 'abc' doesn't match pattern (:num) (\d+)",
            ],

            // 8. Unreplaced named parameter left in URL → fail fast di missing param
            'Unreplaced named parameter left in URL' => [
                [
                    'uri'      => '/user/(id:num)/(slug:any)',
                    'patterns' => [],
                ],
                ['id' => 1],
                'Missing named parameter: slug',
            ],

            // 9. Unreplaced pattern left in URL → fail fast di missing param
            'Unreplaced pattern left in URL' => [
                [
                    'uri'      => '/user/(:num)/(:any)',
                    'patterns' => [],
                ],
                [1],
                'Missing parameter at index 1 for pattern (:any)',
            ],
        ];
    }

    /**
     * Test that RouteUrlBuilder throws InvalidArgumentException for invalid input.
     *
     * This method uses the DataProvider 'invalidArgumentCases' to run multiple
     * scenarios where URL building should fail due to invalid or missing parameters.
     *
     * @param array  $route           The route definition, including URI and custom patterns.
     * @param array  $parameters      The parameters provided for building the URL.
     *                              - Associative arrays match named placeholders in the route.
     *                              - Indexed arrays match positional placeholders.
     * @param string $expectedMessage The expected exception message thrown by RouteUrlBuilder.
     * @return void
     * @throws InvalidArgumentException When the provided parameters do not satisfy the route pattern.
     */
    #[DataProvider('invalidArgumentCases')]
    public function testInvalidArguments(array $route, array $parameters, string $expectedMessage): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $route   = new Route($route);
        $builder = new RouteUrlBuilder([
            '(:num)' => '\d+',
            '(:any)' => '.+',
        ]);

        $builder->buildUrl($route, $parameters);
    }
}
