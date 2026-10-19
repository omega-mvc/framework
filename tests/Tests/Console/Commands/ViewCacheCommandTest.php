<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\ViewCacheCommand;
use Omega\View\Templator;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\FakeTemplator;
use Tests\TestCase;

#[CoversClass(ViewCacheCommand::class)]
final class ViewCacheCommandTest extends TestCase
{
    public function testReportsAnErrorWhenThePathViewBindingIsNotAString(): void
    {
        $base = sys_get_temp_dir() . '/omegavc-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('path.view', 123);

        $command = new ViewCacheCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'The "path.view" binding must resolve to a string path.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testReportsWhenThereAreNoViewFiles(): void
    {
        $base = sys_get_temp_dir() . '/omegavc-' . bin2hex(random_bytes(4));
        $viewPath = $base . '/views';
        mkdir($viewPath, 0777, true);

        $app = new Application($base);
        $app->set('path.view', $viewPath);

        $command = new ViewCacheCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('No view files found.', $result->getDisplay());

        $app->flush();
    }

    public function testCompilesEveryFoundViewFile(): void
    {
        $base = sys_get_temp_dir() . '/omegavc-' . bin2hex(random_bytes(4));
        mkdir($base . '/views', 0777, true);
        file_put_contents($base . '/views/welcome.view.php', '<h1>Welcome</h1>');
        file_put_contents($base . '/views/page.view.php', '<p>Page</p>');

        $templator = new FakeTemplator();

        $app = new Application($base);
        $app->set('path.view', $base . '/views');
        $app->set(Templator::class, $templator);

        $command = new ViewCacheCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('View cache built successfully.', $result->getDisplay());
        $this->assertCount(2, $templator->compiled);

        $app->flush();
    }

    public function testReportsAnErrorWhenAViewFileCannotBeCompiled(): void
    {
        $base = sys_get_temp_dir() . '/omegavc-' . bin2hex(random_bytes(4));
        mkdir($base . '/views', 0777, true);
        file_put_contents($base . '/views/broken.view.php', 'broken');

        $templator = new FakeTemplator();
        $templator->throw = new RuntimeException('Syntax error');

        $app = new Application($base);
        $app->set('path.view', $base . '/views');
        $app->set(Templator::class, $templator);

        $command = new ViewCacheCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'Error compiling /broken.view.php: Syntax error',
            $result->getDisplay()
        );

        $app->flush();
    }
}
