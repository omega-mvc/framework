<?php

declare(strict_types=1);

namespace Tests\Router;

use Omega\Router\Route;
use Omega\Router\Router;
use Omega\Router\RouteUrlBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Route::class)]
#[CoversClass(Router::class)]
#[CoversClass(RouteUrlBuilder::class)]
final class UrlBuilderTest extends TestCase
{
    private RouteUrlBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new RouteUrlBuilder(Router::$patterns);
    }

    public function testCanGenerateSimpleStandardPattern(): void
    {
        $this->assertSame('/user/123', $this->builder->buildUrl(
            new Route([
                'uri' => '/user/(:id)',
            ]),
            [123]
        ));
    }

    public function testCanGenerateMultipleStandardPatterns(): void
    {
        $this->assertSame('/user/123/profile/john-doe', $this->builder->buildUrl(
            new Route([
                'uri' => '/user/(:id)/profile/(:slug)',
            ]),
            [123, 'john-doe']
        ));
    }

    public function testCanGenerateWithNamedParametersOnly(): void
    {
        $this->assertSame('/absensi/456/today', $this->builder->buildUrl(
            new Route([
                'uri' => '/absensi/(identitas:id)/(tanggal:text)',
            ]),
            [
                'identitas' => 456,
                'tanggal'   => 'today',
            ]
        ));
    }

    public function testCanMixIndexedAndNamedParameters(): void
    {
        $this->assertSame('/user/123/absensi/456/hari-ini', $this->builder->buildUrl(
            new Route([
                'uri' => '/user/(:id)/absensi/(identitas:id)/hari-ini',
            ]),
            [
                0           => 123,
                'identitas' => 456,
            ]
        ));
    }

    public function testCanGenerateWithBasePath(): void
    {
        $this->assertSame('/admin/users/999/edit', $this->builder->buildUrl(
            new Route([
                'uri' => '/admin/(section:text)/(userId:id)/edit',
            ]),
            [
                'section' => 'users',
                'userId'  => 999,
            ]
        ));
    }

    public function testCanGenerateWithAllPatternTypes(): void
    {
        $this->assertSame('/api/1/query_123/page/5/active-users', $this->builder->buildUrl(
            new Route([
                'uri' => '/api/(:id)/(search:any)/page/(:num)/(filter:slug)',
            ]),
            [
                'id'     => 1,
                'search' => 'query_123',
                'num'    => 5,
                'filter' => 'active-users',
            ]
        ));
    }

    public function testCanGenerateWithCustomPattern(): void
    {
        $this->assertSame('/color/ff00ff', $this->builder->buildUrl(
            new Route([
                'uri'      => '/color/(:hex)',
                'patterns' => ['(:hex)' => '([0-9a-fA-F]+)'],
            ]),
            ['ff00ff']
        ));
    }

    public function testCanHandleZeroAndEmptyStringValues(): void
    {
        $this->assertSame('/user/0/profile/', $this->builder->buildUrl(
            new Route([
                'uri' => '/user/(:id)/profile/(:text)',
            ]),
            [0, '']
        ));
    }

    public function testCanGenerateComplexNestedStyle(): void
    {
        $this->assertSame('/company/1/employee/456/profile/john-doe/large', $this->builder->buildUrl(
            new Route([
                'uri' => '/company/(:id)/employee/(empId:num)/profile/(:slug)/(avatar:text)',
            ]),
            [
                0        => 1,
                'empId'  => 456,
                1        => 'john-doe',
                'avatar' => 'large',
            ]
        ));
    }

    public function testCanGenerateMultipleSamePatternTypes(): void
    {
        $this->assertSame('/tags/php/related/laravel', $this->builder->buildUrl(
            new Route([
                'uri' => '/tags/(:slug)/related/(:slug)',
            ]),
            ['php', 'laravel']
        ));
    }
}
