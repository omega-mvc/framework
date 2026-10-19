<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\ViewClearCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

#[CoversClass(ViewClearCommand::class)]
final class ViewClearCommandTest extends TestCase
{
    public function testReportsAnErrorWhenThePathCompiledViewPathBindingIsNotAString(): void
    {
        $base = sys_get_temp_dir() . '/omegavclr-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('path.compiled_view_path', 123);

        $command = new ViewClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'The "path.compiled_view_path" binding must resolve to a string path.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testReportsACountOfZeroWhenThereAreNoCachedFiles(): void
    {
        $base = sys_get_temp_dir() . '/omegavclr-' . bin2hex(random_bytes(4));
        $compiled = $base . '/compiled';
        mkdir($compiled, 0777, true);

        $app = new Application($base);
        $app->set('path.compiled_view_path', $compiled);

        $command = new ViewClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Cleared 0 cached files.', $result->getDisplay());

        $app->flush();
    }

    public function testClearsEveryCachedFileRecursively(): void
    {
        $base = sys_get_temp_dir() . '/omegavclr-' . bin2hex(random_bytes(4));
        $compiled = $base . '/compiled';
        mkdir($compiled . '/nested', 0777, true);
        file_put_contents($compiled . '/a.php', 'x');
        file_put_contents($compiled . '/b.php', 'y');
        file_put_contents($compiled . '/nested/c.php', 'z');

        $app = new Application($base);
        $app->set('path.compiled_view_path', $compiled);

        $command = new ViewClearCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Cleared 3 cached files.', $result->getDisplay());
        $this->assertFileDoesNotExist($compiled . '/a.php');
        $this->assertFileDoesNotExist($compiled . '/b.php');
        $this->assertFileDoesNotExist($compiled . '/nested/c.php');

        $app->flush();
    }

    public function testDoesNotCountFilesThatCannotBeRemoved(): void
    {
        $base = sys_get_temp_dir() . '/omegavclr-' . bin2hex(random_bytes(4));
        $compiled = $base . '/compiled';
        mkdir($compiled, 0777, true);
        file_put_contents($compiled . '/locked.php', 'x');

        $probe = $compiled . '/probe.php';
        file_put_contents($probe, 'y');
        @chmod($compiled, 0o555);

        set_error_handler(static fn (int $severity, string $message): bool => true);

        try {
            $probeRemoved = @unlink($probe);
        } finally {
            restore_error_handler();
        }

        if ($probeRemoved) {
            @chmod($compiled, 0o777);
            $this->markTestSkipped('Running with elevated privileges.');
        }

        $app = new Application($base);
        $app->set('path.compiled_view_path', $compiled);

        $command = new ViewClearCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        try {
            $result = (new CommandTester($command))->run([]);
        } finally {
            restore_error_handler();
        }

        @chmod($compiled, 0o777);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Cleared 0 cached files.', $result->getDisplay());
        $this->assertFileExists($compiled . '/locked.php');

        $app->flush();
    }
}
