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

namespace Tests\Router\Attribute;

use Omega\Router\Attribute\Route\Delete;
use Omega\Router\Attribute\Route\Get;
use Omega\Router\Attribute\Route\Post;
use Omega\Router\Attribute\Route\Route;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class TestBasicRouteAttribute
 *
 * Provides a set of basic test methods annotated with HTTP route attributes
 * to simulate typical CRUD operations on a resource. This class is used
 * to validate the behavior of route attribute parsing in the Omega Router
 * system.
 *
 * Each method is decorated with a route attribute specifying the HTTP
 * method(s) and URI pattern:
 *   - index(): GET /
 *   - create(): GET /create
 *   - store(): POST /
 *   - show(int $id): GET /(:id)
 *   - edit(int $id): GET /(:id)/edit
 *   - update(int $id): PUT/PATCH /(:id)
 *   - destroy(int $id): DELETE /(:id)
 *
 * These methods are intended to be scanned by the router for attribute-based
 * route registration and are not meant to contain any actual business logic.
 *
 * @category   Tests
 * @package    Router
 * @subpackage Attribute
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(Delete::class)]
#[CoversClass(Get::class)]
#[CoversClass(Post::class)]
#[CoversClass(Route::class)]
final class TestBasicRouteAttribute
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
