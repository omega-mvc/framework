<?php

declare(strict_types=1);

namespace Tests\Text;

use InvalidArgumentException;
use Omega\Text\Exceptions\AbstractTextException;
use Omega\Text\Exceptions\NoReturnException;
use Omega\Text\Exceptions\PropertyNotExistException;
use Omega\Text\Exceptions\TextExceptionInterface;

use function class_implements;
use function class_parents;
use function expect;

covers(AbstractTextException::class);
covers(NoReturnException::class);
covers(PropertyNotExistException::class);

it('throws a formatted NoReturnException message', function (): void {
    $exception = new NoReturnException('slug', '-~+-');

    expect($exception->getMessage())->toBe('The method slug called with -~+- did not return anything.');
});

it('extends the abstract text exception', function (): void {
    expect(class_parents(NoReturnException::class))->toContain(AbstractTextException::class);
});

it('is an invalid argument exception', function (): void {
    expect(class_parents(NoReturnException::class))->toContain(InvalidArgumentException::class);
});

it('implements the text exception interface', function (): void {
    expect(class_implements(NoReturnException::class))->toContain(TextExceptionInterface::class);
});

it('throws a formatted PropertyNotExistException message', function (): void {
    $exception = new PropertyNotExistException('foo');

    expect($exception->getMessage())->toBe('Property `foo` not exist.');
});

it('is a proper property exception', function (): void {
    expect(class_parents(PropertyNotExistException::class))->toContain(AbstractTextException::class);
});