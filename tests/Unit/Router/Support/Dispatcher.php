<?php

declare(strict_types=1);

namespace Tests\Router\Support;

use Omega\Http\Request;
use Omega\Router\RouteDispatcher;
use Omega\Router\Router;

use function call_user_func;
use function call_user_func_array;
use function class_exists;
use function is_array;
use function is_callable;
use function is_string;
use function ob_get_clean;
use function ob_start;

/**
 * Simulate a request and dispatch a route.
 *
 * @param string $url    The request URI to simulate.
 * @param string $method The HTTP method to simulate.
 * @return false|string Returns the route output or false if the route cannot be dispatched.
 */
function dispatcher(string $url, string $method): false|string
{
    $request  = new Request($url, [], [], [], [], [], [], $method);
    $dispatch = new RouteDispatcher($request, Router::getRoutesRaw());

    $call = $dispatch->run(
        // found
        function ($callable, $param) {
            if (is_array($callable)) {
                [$class, $method] = $callable;

                if (is_string($class) && class_exists($class) && is_string($method)) {
                    $callback = [new $class(), $method];

                    if (is_callable($callback)) {
                        return call_user_func_array($callback, is_array($param) ? $param : []);
                    }
                }
            }

            if (is_callable($callable)) {
                return call_user_func($callable, $param);
            }

            return null;
        },
        // not found
        function ($path) {
            echo 'not found';
        },
        // method not allowed
        function ($path, $method) {
            echo 'not allowed';
        }
    );

    ob_start();
    call_user_func_array($call['callable'], $call['params']);

    return ob_get_clean();
}