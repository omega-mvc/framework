<?php

declare(strict_types=1);

use Omega\SerializableClosure\SerializableClosure;
use Omega\SerializableClosure\UnsignedSerializableClosure;

return [
    // Unsigned serializable closure: expands into one route per HTTP method.
    [
        'expression' => '/closure',
        'function'   => serialize(new UnsignedSerializableClosure(static fn (): string => 'cached-closure')),
        'method'     => ['get', 'head'],
    ],
    // Plain string callable.
    [
        'expression' => '/strlen',
        'function'   => 'strlen',
        'method'     => 'get',
    ],
    // Signed closure serialization: not an UnsignedSerializableClosure, so the entry is dropped.
    [
        'expression' => '/signed',
        'function'   => serialize(new SerializableClosure(static fn (): string => 'signed')),
        'method'     => 'get',
    ],
    // Non-callable value: the whole entry is rejected by the guard.
    [
        'expression' => '/nope',
        'function'   => 'Missing\\Class\\NotACallable',
        'method'     => 'get',
    ],
    // Non-string expression: rejected by the guard.
    [
        'expression' => ['bad'],
        'function'   => static fn (): string => 'x',
        'method'     => 'get',
    ],
    // Empty method array: rejected by the guard.
    [
        'expression' => '/empty',
        'function'   => static fn (): string => 'x',
        'method'     => [],
    ],
    // Method array with a non-string member: only string members are registered.
    [
        'expression' => '/mixed',
        'function'   => static fn (): string => 'x',
        'method'     => ['get', 42],
    ],
    // Method that is neither a string nor an array: rejected by the guard.
    [
        'expression' => '/numeric-method',
        'function'   => 'strlen',
        'method'     => 42,
    ],
    // Non-array entry: skipped during registration.
    'not-an-array',
];