<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\Exceptions\RouteUrlNotFullyResolvedException;
use Omega\Router\Route;
use Omega\Router\Router;
use Omega\Router\RouteUrlBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Route::class)]
#[CoversClass(RouteUrlBuilder::class)]
final class UrlBuilderEdgeTest extends TestCase
{
    public function testManagesCustomPatternsThroughAddPatternsAndGetPatterns(): void
    {
        $builder = new RouteUrlBuilder(['(:a)' => '[a-z]+']);

        $builder->addPatterns(['(:b)' => '\d+']);

        $this->assertSame([
            '(:a)' => '[a-z]+',
            '(:b)' => '\d+',
        ], $builder->getPatterns());
    }

    public function testBuildsAnEmptyUrlWhenTheRouteExposesNoUri(): void
    {
        $builder = new RouteUrlBuilder(Router::$patterns);

        $this->assertSame('', $builder->buildUrl(new Route(['name' => 'nameless']), []));
    }

    public function testSkipsPatternMapEntriesWhoseKeyOrValueIsNotAString(): void
    {
        $builder = new RouteUrlBuilder(Router::$patterns);

        $route = new Route([
            'uri' => '/x',
        ]);
        $route['patterns'] = [0 => 1];

        $this->assertSame('/x', $builder->buildUrl($route, []));
    }

    public function testResolvesPatternPlaceholdersFromNumericKeysInAssociativeParameterSets(): void
    {
        $builder = new RouteUrlBuilder(Router::$patterns);

        $this->assertSame('/user/55', $builder->buildUrl(
            new Route(['uri' => '/user/(:id)']),
            ['other' => 'zz', 0 => 55]
        ));
    }

    public function testResolvesANamedPlaceholderFromANumericParameterList(): void
    {
        $builder = new RouteUrlBuilder(Router::$patterns);

        $this->assertSame('/user/42', $builder->buildUrl(new Route(['uri' => '/user/(id:num)']), [42]));
    }

    public function testThrowsWhenANamedPlaceholderRemainsUnresolvedInTheUrl(): void
    {
        $builder = new RouteUrlBuilder(Router::$patterns);

        $this->expectException(RouteUrlNotFullyResolvedException::class);
        $this->expectExceptionMessageIsOrContains('Unresolved named placeholders remain in the generated URL.');

        $builder->buildUrl(new Route(['uri' => '/user/(pid:all)']), ['pid' => '(x:y)']);
    }

    public function testThrowsWhenARegisteredPatternRemainsInTheGeneratedUrl(): void
    {
        $builder = new RouteUrlBuilder([
            'abc' => '.*',
            '(:x)' => '.*',
        ]);

        $this->expectException(RouteUrlNotFullyResolvedException::class);
        $this->expectExceptionMessageIsOrContains('Unresolved pattern "abc" remains in the generated URL.');

        $builder->buildUrl(new Route(['uri' => '/u/(:x)']), ['x' => 'abc']);
    }
}
