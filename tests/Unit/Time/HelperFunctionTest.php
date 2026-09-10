<?php

/**
 * Part of Omega - Tests\Time Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Time;

use Omega\Time\Now;

use function Omega\Time\now;

covers(Now::class, 'Omega\Time\now');

it('can use the global now helper function', function (): void {
    $instance = now('2020-01-01', 'UTC');

    expect($instance->getYear())->toBe(2020);
});