<?php

/**
 * Part of Omega - Router Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Router;

use Omega\Collection\Collection;
use Omega\Collection\CollectionImmutable;

use function array_key_exists;

/**
 * Class Resource
 *
 * Handles the creation and management of RESTful resource routes
 * for a given controller. Generates routes for standard resource actions
 * such as index, create, store, show, edit, update, and destroy.
 *
 * Each generated route can include middleware, HTTP methods, and URI patterns.
 *
 * @category  Omega
 * @package   Router
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Resource
{
    /**
     * Collection of resource routes.
     *
     * Keys are the resource action names and values are Route instances.
     *
     * @var Collection<string, Route>
     */
    private Collection $resource;

    /**
     * Returns the list of standard RESTful resource methods.
     *
     * @return array<string, string> An associative array where keys and values are
     *                                the standard resource action names.
     */
    public static function method(): array
    {
        return [
            'index'   => 'index',
            'create'  => 'create',
            'store'   => 'store',
            'show'    => 'show',
            'edit'    => 'edit',
            'update'  => 'update',
            'destroy' => 'destroy',
        ];
    }

    /**
     * Resource constructor.
     *
     * Initializes a collection of resource routes and generates
     * routes based on the provided URI, controller class, and action map.
     *
     * @param string                $url       Base URI for the resource.
     * @param class-string          $className Controller class handling the resource.
     * @param array<string, string> $map       Mapping of resource actions to method names.
     */
    public function __construct(string $url, string $className, array $map)
    {
        $this->resource = new Collection([]);
        $this->generate($url, $className, $map);
    }

    /**
     * Generates resource routes for the given URI, controller, and action map.
     *
     * @param string                $uri       Base URI for the resource.
     * @param class-string          $className Controller class handling the resource.
     * @param array<string, string> $map       Mapping of resource actions to method names.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function generate(string $uri, string $className, array $map): self
    {
        $uri  = Router::$group['prefix'] . $uri;

        if (array_key_exists('index', $map)) {
            $this->resource->set(
                $map['index'],
                new Route([
                    'expression' => Router::mapPatterns($uri, Router::$patterns),
                    'function'   => [$className, $map['index']],
                    'method'     => 'get',
                    'middleware' => Router::$group['middleware'] ?? [],
                ])->name("{$className}.index")
            );
        }

        if (array_key_exists('create', $map)) {
            $this->resource->set(
                $map['create'],
                new Route([
                    'expression' => Router::mapPatterns("{$uri}create", Router::$patterns),
                    'function'   => [$className, $map['create']],
                    'method'     => 'get',
                    'middleware' => Router::$group['middleware'] ?? [],
                ])->name("{$className}.create")
            );
        }

        if (array_key_exists('store', $map)) {
            $this->resource->set(
                $map['store'],
                new Route([
                    'expression' => Router::mapPatterns($uri, Router::$patterns),
                    'function'   => [$className, $map['store']],
                    'method'     => 'post',
                    'middleware' => Router::$group['middleware'] ?? [],
                ])->name("{$className}.store")
            );
        }

        if (array_key_exists('show', $map)) {
            $this->resource->set(
                $map['show'],
                new Route([
                    'expression' => Router::mapPatterns("{$uri}(:id)", Router::$patterns),
                    'function'   => [$className, $map['show']],
                    'method'     => 'get',
                    'middleware' => Router::$group['middleware'] ?? [],
                ])->name("{$className}.show")
            );
        }

        if (array_key_exists('edit', $map)) {
            $this->resource->set(
                $map['edit'],
                new Route([
                    'expression' => Router::mapPatterns("{$uri}(:id)/edit", Router::$patterns),
                    'function'   => [$className, $map['edit']],
                    'method'     => 'get',
                    'middleware' => Router::$group['middleware'] ?? [],
                ])->name("{$className}.edit")
            );
        }

        if (array_key_exists('update', $map)) {
            $this->resource->set(
                $map['update'],
                new Route([
                    'expression' => Router::mapPatterns("{$uri}(:id)", Router::$patterns),
                    'function'   => [$className, $map['update']],
                    'method'     => ['put', 'patch'],
                    'middleware' => Router::$group['middleware'] ?? [],
                ])->name("{$className}.update")
            );
        }

        if (array_key_exists('destroy', $map)) {
            $this->resource->set(
                $map['destroy'],
                new Route([
                    'expression' => Router::mapPatterns("{$uri}(:id)", Router::$patterns),
                    'function'   => [$className, $map['destroy']],
                    'method'     => 'delete',
                    'middleware' => Router::$group['middleware'] ?? [],
                ])->name("{$className}.destroy")
            );
        }

        return $this;
    }

    /**
     * Returns an immutable collection of the generated resource routes.
     *
     * @return CollectionImmutable<string, Route> The collection of resource routes.
     */
    public function get(): CollectionImmutable
    {
        return $this->resource->immutable();
    }

    /**
     * Filters the resource collection to include only the specified actions.
     *
     * @param string[] $resource List of resource action names to keep.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function only(array $resource): self
    {
        $this->resource->only($resource);

        return $this;
    }

    /**
     * Filters the resource collection to exclude the specified actions.
     *
     * @param string[] $resource List of resource action names to remove.
     *
     * @return self Returns the current instance for method chaining.
     */
    public function except(array $resource): self
    {
        $this->resource->except($resource);

        return $this;
    }
}
