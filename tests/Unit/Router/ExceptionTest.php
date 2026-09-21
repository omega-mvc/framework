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

use JsonSerializable;
use Omega\Router\Exceptions\InvalidRouteParameterException;
use Omega\Router\Exceptions\MissingRouteParameterException;
use Omega\Router\Exceptions\RouteNotFoundException;
use Omega\Router\Exceptions\RouteNotRegisteredException;
use Omega\Router\Exceptions\RouteUrlNotFullyResolvedException;
use Omega\Router\Exceptions\UnknownRoutePatternException;
use RuntimeException;
use Stringable;
use stdClass;

use function fopen;

covers(InvalidRouteParameterException::class);
covers(MissingRouteParameterException::class);
covers(RouteNotFoundException::class);
covers(RouteNotRegisteredException::class);
covers(RouteUrlNotFullyResolvedException::class);
covers(UnknownRoutePatternException::class);

it('builds the route not found message from the route name', function (): void {
    $exception = new RouteNotFoundException('users.show');

    expect($exception->getMessage())->toBe('Route [users.show] not found.');
});

it('builds the route not registered message from the property name', function (): void {
    $exception = new RouteNotRegisteredException('uri');

    expect($exception->getMessage())->toBe('Route property or method [uri] is not registered.');
});

it('builds the unknown pattern message from the pattern key', function (): void {
    $exception = new UnknownRoutePatternException('(:custom)');

    expect($exception->getMessage())->toBe('Unknown pattern type: (:custom)');
});

it('builds every missing route parameter message variant', function (): void {
    expect(MissingRouteParameterException::named('id')->getMessage())->toBe('Missing named parameter: id');
    expect(MissingRouteParameterException::namedIndexed(2, 'slug')->getMessage())
        ->toBe('Missing parameter at index 2 for named parameter slug');
    expect(MissingRouteParameterException::patternAssoc('(:num)', 3)->getMessage())
        ->toBe("Missing parameter for pattern {(:num)}. Provide either numeric index {3} or key '{num}'");
    expect(MissingRouteParameterException::patternIndexed(4, '(:any)')->getMessage())
        ->toBe('Missing parameter at index 4 for pattern (:any)');
});

it('carries the message of the route url not fully resolved exception', function (): void {
    $exception = new RouteUrlNotFullyResolvedException('Unresolved pattern remains.');

    expect($exception)->toBeInstanceOf(RuntimeException::class);
    expect($exception->getMessage())->toBe('Unresolved pattern remains.');
});

it('stringifies scalar, stringable, serializable and nested values', function (): void {
    $stringable = new class implements Stringable {
        public function __toString(): string
        {
            return 'str-value';
        }
    };
    $jsonable = new class implements JsonSerializable {
        public function jsonSerialize(): mixed
        {
            return ['key' => 'value'];
        }
    };

    expect((new InvalidRouteParameterException('a', 'text'))->getMessage())
        ->toBe('Invalid value [text] for route parameter [a].');
    expect((new InvalidRouteParameterException('a', 7))->getMessage())
        ->toBe('Invalid value [7] for route parameter [a].');
    expect((new InvalidRouteParameterException('a', 1.5))->getMessage())
        ->toBe('Invalid value [1.5] for route parameter [a].');
    expect((new InvalidRouteParameterException('a', true))->getMessage())
        ->toBe('Invalid value [1] for route parameter [a].');
    expect((new InvalidRouteParameterException('a', $stringable))->getMessage())
        ->toBe('Invalid value [str-value] for route parameter [a].');
    expect((new InvalidRouteParameterException('a', $jsonable))->getMessage())
        ->toBe('Invalid value [{"key":"value"}] for route parameter [a].');
    expect((new InvalidRouteParameterException('a', ['x' => 1]))->getMessage())
        ->toBe('Invalid value [{"x":1}] for route parameter [a].');
    expect((new InvalidRouteParameterException('a', new stdClass()))->getMessage())
        ->toBe('Invalid value [{}] for route parameter [a].');

    $resource = fopen('php://memory', 'r');

    expect((new InvalidRouteParameterException('a', $resource))->getMessage())
        ->toBe('Invalid value [null] for route parameter [a].');
    expect((new InvalidRouteParameterException('a', [$resource]))->getMessage())
        ->toBe('Invalid value [null] for route parameter [a].');
});