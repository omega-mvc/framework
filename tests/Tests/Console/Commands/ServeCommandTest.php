<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\ServeCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

#[CoversClass(ServeCommand::class)]
final class ServeCommandTest extends TestCase
{
    public function testReportsAnErrorWhenThePortIsNotNumeric(): void
    {
        $base = sys_get_temp_dir() . '/omegas-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new ServeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--port' => 'abc']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'The port must be an integer between 1 and 65535.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testReportsAnErrorWhenThePortIsBelowTheValidRange(): void
    {
        $base = sys_get_temp_dir() . '/omegas-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new ServeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--port' => '0']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'The port must be an integer between 1 and 65535.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testReportsAnErrorWhenThePortExceedsTheValidRange(): void
    {
        $base = sys_get_temp_dir() . '/omegas-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new ServeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--port' => '70000']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'The port must be an integer between 1 and 65535.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testReportsAnErrorWhenThePortIsAlreadyInUse(): void
    {
        $base = sys_get_temp_dir() . '/omegas-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $server = stream_socket_server('tcp://127.0.0.1:0');

        if ($server === false) {
            $this->fail('Could not create a local socket server for the port-in-use test.');
        }

        $name = stream_socket_get_name($server, false);

        if ($name === false) {
            $this->fail('Could not resolve a local socket port for the port-in-use test.');
        }

        $port = (int) substr(strrchr($name, ':') ?: '', 1);

        $command = new ServeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--port' => (string) $port]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString("The port {$port} is already in use.", $result->getDisplay());

        fclose($server);
        $app->flush();
    }

    public function testPrintsTheServerBannerBeforeResolvingThePublicPath(): void
    {
        $base = sys_get_temp_dir() . '/omegas-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('path.public', 123);

        $port = random_int(20000, 50000);

        $command = new ServeCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        try {
            $result = (new CommandTester($command))->run(['--port' => (string) $port]);
        } finally {
            restore_error_handler();
        }

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('The "path.public" binding must resolve to a string path.', $display);
        $this->assertStringContainsString('Server running at:', $display);
        $this->assertStringContainsString('Local:', $display);
        $this->assertStringContainsString("http://127.0.0.1:{$port}", $display);
        $this->assertStringContainsString('Server running...', $display);

        $app->flush();
    }

    public function testPrintsTheNetworkAddressWhenTheServerIsExposed(): void
    {
        $base = sys_get_temp_dir() . '/omegas-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('path.public', 123);

        $port = random_int(20000, 50000);

        $command = new ServeCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        try {
            $result = (new CommandTester($command))->run(['--port' => (string) $port, '--expose' => true]);
        } finally {
            restore_error_handler();
        }

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Network:', $display);
        $this->assertStringContainsString("http://127.0.0.1:{$port}", $display);

        $app->flush();
    }

    public function testLaunchesTheBuiltInServerWithTheResolvedPublicPath(): void
    {
        $base = sys_get_temp_dir() . '/omegas-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('path.public', $base . '/missing-public');

        $command = new ServeCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--port' => (string) $this->findFreePort()]);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Server running...', $display);

        $app->flush();
    }

    private function findFreePort(): int
    {
        $server = stream_socket_server('tcp://127.0.0.1:0');

        if ($server === false) {
            $this->fail('Could not create a local socket server to reserve a port.');
        }

        $name = stream_socket_get_name($server, false);

        if ($name === false) {
            fclose($server);
            $this->fail('Could not resolve a local socket port.');
        }

        $port = (int) substr(strrchr($name, ':') ?: '', 1);

        fclose($server);

        return $port;
    }
}
