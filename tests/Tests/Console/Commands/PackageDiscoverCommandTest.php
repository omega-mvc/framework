<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Application\ApplicationManifest;
use Omega\Console\Commands\PackageDiscoverCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\FakeApplicationManifest;
use Tests\TestCase;

#[CoversClass(PackageDiscoverCommand::class)]
final class PackageDiscoverCommandTest extends TestCase
{
    public function testReportsWhenNoDiscoverablePackagesAreFound(): void
    {
        $base = sys_get_temp_dir() . '/omegapd-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $manifest = new FakeApplicationManifest();
        $manifest->data = [];
        $app->set(ApplicationManifest::class, $manifest);

        $command = new PackageDiscoverCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Discovery packages in composer...', $result->getDisplay());
        $this->assertStringContainsString('No discoverable packages found.', $result->getDisplay());

        $app->flush();
    }

    public function testListsTheDiscoveredPackagesAndReportsSuccess(): void
    {
        $base = sys_get_temp_dir() . '/omegapd-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $manifest = new FakeApplicationManifest();
        $manifest->data = [
            'vendor/package-a' => ['providers' => ['A\\Provider']],
            'vendor/package-b' => ['providers' => ['B\\Provider']],
        ];
        $app->set(ApplicationManifest::class, $manifest);

        $command = new PackageDiscoverCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('vendor/package-a', $result->getDisplay());
        $this->assertStringContainsString('vendor/package-b', $result->getDisplay());
        $this->assertStringContainsString('DONE', $result->getDisplay());
        $this->assertStringContainsString('Package manifest generated successfully.', $result->getDisplay());

        $app->flush();
    }

    public function testReportsAnErrorWhenThePackageDiscoveryFails(): void
    {
        $base = sys_get_temp_dir() . '/omegapd-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $manifest = new FakeApplicationManifest();
        $manifest->throw = new RuntimeException('Packages could not be read');
        $app->set(ApplicationManifest::class, $manifest);

        $command = new PackageDiscoverCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Packages could not be read', $result->getDisplay());
        $this->assertStringContainsString("Can't create package manifest cache file.", $result->getDisplay());

        $app->flush();
    }
}
