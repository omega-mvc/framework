<?php

declare(strict_types=1);

namespace Tests\Environment;

use Omega\Application\Application;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function Omega\Environment\env;

#[CoversClass(Application::class)]
final class HelperTest extends TestCase
{
    public function testEnvHelperReturnsDefaultValueIfKeyDoesNotExist(): void
    {
        $default = 'default_value';

        $this->assertSame($default, env('NON_EXISTING_KEY', $default));
    }
}
