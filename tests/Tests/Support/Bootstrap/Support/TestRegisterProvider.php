<?php

declare(strict_types=1);

namespace Tests\Support\Bootstrap\Support;

use Omega\Container\Provider\AbstractServiceProvider;

class TestRegisterProvider extends AbstractServiceProvider
{
    public static int $called = 0;

    public function register(): void
    {
        self::$called++;
    }
}
