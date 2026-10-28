<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use ArrayObject;
use Omega\Application\Application;
use Omega\Console\Commands\ViewWatchCommand;
use Omega\View\Templator;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionObject;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\DependencyTemplator;
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

    public function testRecompilesChangedViewsAndStopsOnInterrupt(): void
    {
        if (!function_exists('pcntl_alarm') || !function_exists('posix_kill')) {
            self::markTestSkipped('Requires the pcntl extension to interrupt the watch loop.');
        }

        $base = sys_get_temp_dir() . '/omegavw-' . bin2hex(random_bytes(4));
        $views = $base . '/views';
        mkdir($views, 0777, true);

        $page = $views . '/page.view.php';
        $layout = $views . '/layout.view.php';
        $child = $views . '/child.view.php';
        $partial = $views . '/partial.view.php';

        foreach ([$page, $layout, $child, $partial] as $file) {
            file_put_contents($file, '<p>view</p>');
        }

        $templator = new DependencyTemplator();
        $templator->dependencies = [
            $child  => [$layout => time()],
            $layout => [$partial => time()],
        ];

        $app = new Application($base);
        $app->set('path.view', $views);
        $app->set(Templator::class, $templator);

        $command = new ViewWatchCommand();
        $command->app = $app;

        /** @var ArrayObject<int, int> $tick */
        $tick = new ArrayObject([0]);

        $handler = static function () use ($tick, $layout, $page, $partial, $templator): void {
            pcntl_alarm(1);

            $current = $tick[0];
            $tick[0] = $current + 1;

            if (0 === $current) {
                touch($layout, time() + 10);

                return;
            }

            if (1 === $current) {
                $templator->throw = new RuntimeException('compilation failure');
                touch($partial, time() + 20);

                return;
            }

            if (2 === $current) {
                $templator->throw = null;
                unlink($page);

                return;
            }

            posix_kill(posix_getpid(), SIGINT);
        };

        pcntl_async_signals(true);
        pcntl_signal(SIGALRM, $handler);
        pcntl_alarm(1);

        try {
            $result = (new CommandTester($command))->run(['--prefix' => ['*.php']]);
        } finally {
            pcntl_alarm(0);
            pcntl_signal(SIGALRM, SIG_DFL);
        }

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertGreaterThanOrEqual(2, $tick[0]);
        $this->assertStringContainsString('PRE-COMPILE', $display);
        $this->assertStringContainsString('Error compiling', $display);
        $this->assertContains('/layout.view.php', $templator->compiled);
        $this->assertContains('/child.view.php', $templator->compiled);

        $app->flush();
    }
}
