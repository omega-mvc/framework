<?php

declare(strict_types=1);

namespace Tests\Router;

use JsonSerializable;
use Omega\Router\Exceptions\InvalidRouteParameterException;
use Omega\Router\Exceptions\MissingRouteParameterException;
use Omega\Router\Exceptions\RouteNotFoundException;
use Omega\Router\Exceptions\RouteNotRegisteredException;
use Omega\Router\Exceptions\RouteUrlNotFullyResolvedException;
use Omega\Router\Exceptions\UnknownRoutePatternException;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Stringable;
use Tests\TestCase;

use function fopen;

#[CoversClass(InvalidRouteParameterException::class)]
#[CoversClass(MissingRouteParameterException::class)]
#[CoversClass(RouteNotFoundException::class)]
#[CoversClass(RouteNotRegisteredException::class)]
#[CoversClass(RouteUrlNotFullyResolvedException::class)]
#[CoversClass(UnknownRoutePatternException::class)]
final class ExceptionTest extends TestCase
{
    public function testBuildsTheRouteNotFoundMessageFromTheRouteName(): void
    {
        $exception = new RouteNotFoundException('users.show');

        $this->assertSame('Route [users.show] not found.', $exception->getMessage());
    }

    public function testBuildsTheRouteNotRegisteredMessageFromThePropertyName(): void
    {
        $exception = new RouteNotRegisteredException('uri');

        $this->assertSame('Route property or method [uri] is not registered.', $exception->getMessage());
    }

    public function testBuildsTheUnknownPatternMessageFromThePatternKey(): void
    {
        $exception = new UnknownRoutePatternException('(:custom)');

        $this->assertSame('Unknown pattern type: (:custom)', $exception->getMessage());
    }

    public function testBuildsEveryMissingRouteParameterMessageVariant(): void
    {
        $this->assertSame('Missing named parameter: id', MissingRouteParameterException::named('id')->getMessage());
        $this->assertSame(
            'Missing parameter at index 2 for named parameter slug',
            MissingRouteParameterException::namedIndexed(2, 'slug')->getMessage()
        );
        $this->assertSame(
            "Missing parameter for pattern {(:num)}. Provide either numeric index {3} or key '{num}'",
            MissingRouteParameterException::patternAssoc('(:num)', 3)->getMessage()
        );
        $this->assertSame(
            'Missing parameter at index 4 for pattern (:any)',
            MissingRouteParameterException::patternIndexed(4, '(:any)')->getMessage()
        );
    }

    public function testCarriesTheMessageOfTheRouteUrlNotFullyResolvedException(): void
    {
        $exception = new RouteUrlNotFullyResolvedException('Unresolved pattern remains.');

        $this->assertSame('Unresolved pattern remains.', $exception->getMessage());
    }

    public function testStringifiesScalarStringableSerializableAndNestedValues(): void
    {
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

        $this->assertSame(
            'Invalid value [text] for route parameter [a].',
            (new InvalidRouteParameterException('a', 'text'))->getMessage()
        );
        $this->assertSame(
            'Invalid value [7] for route parameter [a].',
            (new InvalidRouteParameterException('a', 7))->getMessage()
        );
        $this->assertSame(
            'Invalid value [1.5] for route parameter [a].',
            (new InvalidRouteParameterException('a', 1.5))->getMessage()
        );
        $this->assertSame(
            'Invalid value [1] for route parameter [a].',
            (new InvalidRouteParameterException('a', true))->getMessage()
        );
        $this->assertSame(
            'Invalid value [str-value] for route parameter [a].',
            (new InvalidRouteParameterException('a', $stringable))->getMessage()
        );
        $this->assertSame(
            'Invalid value [{"key":"value"}] for route parameter [a].',
            (new InvalidRouteParameterException('a', $jsonable))->getMessage()
        );
        $this->assertSame(
            'Invalid value [{"x":1}] for route parameter [a].',
            (new InvalidRouteParameterException('a', ['x' => 1]))->getMessage()
        );
        $this->assertSame(
            'Invalid value [{}] for route parameter [a].',
            (new InvalidRouteParameterException('a', new stdClass()))->getMessage()
        );

        $resource = fopen('php://memory', 'r');

        $this->assertSame(
            'Invalid value [null] for route parameter [a].',
            (new InvalidRouteParameterException('a', $resource))->getMessage()
        );
        $this->assertSame(
            'Invalid value [null] for route parameter [a].',
            (new InvalidRouteParameterException('a', [$resource]))->getMessage()
        );
    }
}
