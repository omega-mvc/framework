<?php

declare(strict_types=1);

namespace Tests\Environment;

use Omega\Application\Application;

use function Omega\Environment\env;

covers(Application::class);

it('env helper returns default value if key does not exist', function (): void {
    $default = 'default_value';

    expect(env('NON_EXISTING_KEY', $default))->toBe($default);
});