<?php

declare(strict_types=1);

return [
    // Controller pair: [class-string, instance method] is not is_callable()
    // (non-static), yet it must survive the cache read — the dispatcher
    // resolves instance methods itself (container in the app, `new $class()`
    // in the test helper), exactly as it does for uncached routes.
    [
        'expression' => '/controller',
        'function'   => ['Tests\Router\Support\SomeClass', 'foo'],
        'method'     => 'get',
    ],
    // Wrong arity: dropped.
    [
        'expression' => '/arity',
        'function'   => ['Tests\Router\Support\SomeClass', 'foo', 'extra'],
        'method'     => 'get',
    ],
    // Non-string method slot: dropped.
    [
        'expression' => '/int-method',
        'function'   => ['Tests\Router\Support\SomeClass', 42],
        'method'     => 'get',
    ],
    // Non object/string class slot: dropped.
    [
        'expression' => '/int-class',
        'function'   => [42, 'foo'],
        'method'     => 'get',
    ],
    // Single-element array: dropped.
    [
        'expression' => '/single',
        'function'   => ['Tests\Router\Support\SomeClass'],
        'method'     => 'get',
    ],
    // Two elements, but not keyed 0 and 1: dropped.
    [
        'expression' => '/not-a-pair',
        'function'   => ['class' => 'Tests\Router\Support\SomeClass', 'method' => 'foo'],
        'method'     => 'get',
    ],
];
