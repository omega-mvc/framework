<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\ViewWatchCommand;
use Omega\View\Templator;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\FakeTemplator;
use Tests\TestCase;

#[CoversClass(ViewWatchCommand::class)]
final class ViewWatchCommandTest extends TestCase
{
    public function testReportsAnErrorWhenThePathViewBindingIsNotAString(): void
    {
        $base = sys_get_temp_dir() . '/omegavw-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('path.view', 123);

        $command = new ViewWatchCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'The "path.view" binding must resolve to a string path.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testReportsAFailureWhenThereAreNoViewFilesToWatch(): void
    {
        $base = sys_get_temp_dir() . '/omegavw-' . bin2hex(random_bytes(4));
        mkdir($base . '/views', 0777, true);

        $app = new Application($base);
        $app->set('path.view', $base . '/views');
        $app->set(Templator::class, new FakeTemplator());

        $command = new ViewWatchCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Watching view files in', $result->getDisplay());

        $app->flush();
    }

    public function testPrecompilesTheIndexedViewsAndExitsWhenTheWatchLoopIsDisabled(): void
    {
        $base = sys_get_temp_dir() . '/omegavw-' . bin2hex(random_bytes(4));
        mkdir($base . '/views', 0777, true);
        file_put_contents($base . '/views/welcome.view.php', '<h1>Welcome</h1>');
        file_put_contents($base . '/views/page.view.php', '<p>Page</p>');

        $templator = new FakeTemplator();

        $app = new Application($base);
        $app->set('path.view', $base . '/views');
        $app->set(Templator::class, $templator);

        $command = new ViewWatchCommand();
        $command->app = $app;

        $reflection = new ReflectionObject($command);
        $shouldExit = $reflection->getProperty('shouldExit');
        $shouldExit->setValue($command, true);

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Watching view files in', $result->getDisplay());
        $this->assertStringContainsString('PRE-COMPILE', $result->getDisplay());
        $this->assertCount(2, $templator->compiled);

        $app->flush();
    }
}
