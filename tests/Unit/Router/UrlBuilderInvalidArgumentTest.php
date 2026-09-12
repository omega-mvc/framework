<?php

declare(strict_types=1);

namespace Tests\Router;

use InvalidArgumentException;
use Omega\Router\Route;
use Omega\Router\RouteUrlBuilder;

use function str_contains;

covers(Route::class);
covers(RouteUrlBuilder::class);

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
 * @return array<string, array{0: array{uri: string, patterns: array<string, string>}, 1: array<int|string, int|string>, 2: string}>
 *         Returns an associative array of test cases keyed by a descriptive name.
 */
function invalidArgumentCases(): array
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

it('throws InvalidArgumentException for invalid input', function (): void {
    $builder = new RouteUrlBuilder([
        '(:num)' => '\d+',
        '(:any)' => '.+',
    ]);

    foreach (invalidArgumentCases() as $name => [$route, $parameters, $expectedMessage]) {
        try {
            $builder->buildUrl(new Route($route), $parameters);
            $this->fail('Expected InvalidArgumentException: ' . $name);
        } catch (InvalidArgumentException $e) {
            expect($e->getMessage() === $expectedMessage || str_contains($e->getMessage(), $expectedMessage))->toBeTrue();
        }
    }
});