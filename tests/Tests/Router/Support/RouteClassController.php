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

namespace Tests\Router\Support;

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Class RouteClassController
 *
 * A simple resource controller class used for testing full resource routing.
 *
 * Contains all standard resource methods for a controller:
 * index, create, store, show, edit, update, destroy.
 *
 * @category   Tests
 * @package    Router
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversNothing]
class RouteClassController
{
    /**
     * Handle the "index" action.
     *
     * This method is called when the index route of the resource is accessed.
     *
     * @return void
     */
    public function index(): void
    {
        echo 'works';
    }

    /**
     * Handle the "create" action.
     *
     * This method is called when the creation route of the resource is accessed.
     *
     * @return void
     */
    public function create(): void
    {
        echo 'works create';
    }

    /**
     * Handle the "store" action.
     *
     * This method is called when the store route of the resource is accessed.
     *
     * @return void
     */
    public function store(): void
    {
        echo 'works store';
    }

    /**
     * Handle the "show" action.
     *
     * This method is called when a specific resource is requested via its ID.
     *
     * @return void
     */
    public function show(): void
    {
        echo 'works show';
    }

    /**
     * Handle the "edit" action.
     *
     * This method is called when the edit route of a specific resource is accessed.
     *
     * @return void
     */
    public function edit(): void
    {
        echo 'works edit';
    }

    /**
     * Handle the "update" action.
     *
     * This method is called when the update route of a specific resource is accessed.
     *
     * @return void
     */
    public function update(): void
    {
        echo 'works update';
    }

    /**
     * Handle the "destroy" action.
     *
     * This method is called when the destroy route of a specific resource is accessed.
     *
     * @return void
     */
    public function destroy(): void
    {
        echo 'works destroy';
    }
}
