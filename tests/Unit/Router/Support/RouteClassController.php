<?php

declare(strict_types=1);

namespace Tests\Router\Support;

use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RouteClassController extends TestCase
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