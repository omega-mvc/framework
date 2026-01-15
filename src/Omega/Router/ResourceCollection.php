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

use Omega\Text\Str;

use function array_diff_key;
use function array_filter;
use function in_array;
use function method_exists;

/**
 * Class ResourceCollection
 *
 * Handles operations on a collection of resource routes for a specific controller class.
 * Provides methods to include or exclude certain resource actions, remap actions,
 * and define behavior for missing controller methods.
 *
 * @category  Omega
 * @package   Router
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
readonly class ResourceCollection
{
    /**
     * ResourceCollection constructor.
     *
     * Initializes the collection for a given controller class.
     *
     * @param class-string $className The fully qualified controller class name.
     * @return void
     */
    public function __construct(private string $className)
    {
    }

    /**
     * Keeps only the specified resource actions in the route collection.
     *
     * Removes any routes that are not in the provided list.
     *
     * @param string[] $resources List of resource action names to keep.
     * @return void
     */
    public function only(array $resources): void
    {
        $map = array_filter(
            Resource::method(),
            fn ($resource) => !in_array($resource, $resources, true)
        );
        foreach ($map as $resource) {
            $name = "{$this->className}.{$resource}";
            if (Router::has($name)) {
                Router::removeRoutes($name);
            }
        }
    }

    /**
     * Removes the specified resource actions from the route collection.
     *
     * @param string[] $resources List of resource action names to remove.
     * @return void
     */
    public function except(array $resources): void
    {
        foreach ($resources as $resource) {
            $name = "{$this->className}.{$resource}";
            if (Router::has($name)) {
                Router::removeRoutes($name);
            }
        }
    }

    /**
     * Remaps existing resource actions to new method names.
     *
     * Allows customizing which controller method handles a specific resource action.
     *
     * @param array<string, string> $resources Associative array mapping resource action keys to new method names.
     * @return void
     */
    public function map(array $resources): void
    {
        $diff = array_diff_key(Resource::method(), $resources);
        $this->except($diff);

        foreach (Router::getRoutes() as $route) {
            foreach ($resources as $key => $resource) {
                $name = "{$this->className}.{$key}";
                if ($name === $route['name']) {
                    $route['function'][1] = $resource;
                    Router::changeRoutes($name, new Route($route));
                    break;
                }
            }
        }
    }

    /**
     * Defines a fallback behavior for missing controller methods.
     *
     * Iterates over resource routes and replaces the route's callable with the
     * provided callable if the target controller method does not exist.
     *
     * @param callable $callable A callable to handle missing methods.
     * @return self Returns the current instance for method chaining.
     */
    public function missing(callable $callable): self
    {
        foreach (Router::getRoutes() as $route) {
            $name = $route['name'];
            if (Str::startsWith($name, "{$this->className}.")) {
                [$class, $method] = $route['function'];
                if (!method_exists($class, $method)) {
                    $route['function'] = $callable;
                    Router::changeRoutes($name, new Route($route));
                }
            }
        }

        return $this;
    }
}
