<?php

declare(strict_types=1);

namespace Tests\Config\Facade;

use Omega\Config\ConfigRepository;
use Omega\Config\Facade\Config;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Config::class)]
final class ConfigFacadeTest extends TestCase
{
    public function testShouldExposeTheConfigRepositoryAccessor(): void
    {
        $this->assertSame(ConfigRepository::class, Config::getFacadeAccessor());
    }
}
