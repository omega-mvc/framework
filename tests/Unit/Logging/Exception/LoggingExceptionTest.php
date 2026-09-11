<?php

declare(strict_types=1);

namespace Tests\Logging\Exception;

use InvalidArgumentException;
use Omega\Logging\Exception\LogArgumentException;
use Omega\Logging\Exception\UnknownDriverException;
use ReflectionClass;

covers(LogArgumentException::class);
covers(UnknownDriverException::class);

it('constructs a LogArgumentException', function (): void {
    $exception = new LogArgumentException('invalid log');
    $parent    = (new ReflectionClass($exception))->getParentClass();

    $this->assertNotFalse($parent);

    expect($parent->getName())->toBe(InvalidArgumentException::class);
    expect($exception->getMessage())->toBe('invalid log');
});

it('constructs an UnknownDriverException', function (): void {
    $exception = new UnknownDriverException('stream');

    expect($exception->getMessage())
        ->toBe('The log driver "stream" could not be resolved or is not registered.');
});
