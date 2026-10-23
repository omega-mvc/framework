<?php

declare(strict_types=1);

namespace Tests\Router\Attribute;

use Omega\Router\Attribute\Route\Delete;
use Omega\Router\Attribute\Route\Get;
use Omega\Router\Attribute\Route\Post;
use Omega\Router\Attribute\Route\Route;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class TestBasicRouteAttribute extends TestCase
{
    /**
     * Test method index.
     *
     * @return void
     */
    #[Get('/')]
    public function index(): void
    {
    }

    /**
     * Test method create.
     *
     * @return void
     */
    #[Get('/create')]
    public function create(): void
    {
    }

    /**
     * Test method store.
     *
     * @return void
     */
    #[Post('/')]
    public function store(): void
    {
    }

    /**
     * Test method show.
     *
     * @param int $id The of the resource.
     * @return void
     */
    #[Get('/(:id)')]
    public function show(int $id): void
    {
    }

    /**
     * Test method edit.
     *
     * @param int $id The of the resource.
     * @return void
     */
    #[Get('/(:id)/edit')]
    public function edit(int $id): void
    {
    }

    /**
     * Test method update.
     *
     * @param int $id The of the resource.
     * @return void
     */
    #[Route(['put', 'patch'], '/(:id)')]
    public function update(int $id): void
    {
    }

    /**
     * Test method destroy.
     *
     * @param int $id The of the resource.
     * @return void
     */
    #[Delete('/(:id)')]
    public function destroy(int $id): void
    {
    }
}
