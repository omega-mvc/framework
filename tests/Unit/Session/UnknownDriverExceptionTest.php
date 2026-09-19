<?php

/**
 * Part of Omega - Tests\Session Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Session;

use Omega\Session\Exceptions\UnknownDriverException;
use RuntimeException;

use function class_parents;
use function expect;

covers(UnknownDriverException::class);

it('constructs an UnknownDriverException', function (): void {
    $exception = new UnknownDriverException('redis');

    expect(class_parents(UnknownDriverException::class))->toContain(RuntimeException::class);
    expect($exception->getMessage())
        ->toBe('The session storage driver "redis" could not be resolved or is not registered.');
});

it('reports the driver name that could not be resolved', function (): void {
    $exception = new UnknownDriverException('file');

    expect($exception->getMessage())->toContain('"file"');
});