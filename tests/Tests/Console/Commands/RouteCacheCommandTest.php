<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\RouteCacheCommand;
use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

#[CoversClass(RouteCacheCommand::class)]
final class RouteCacheCommandTest extends TestCase
{
    public function testReportsWhenThereAreNoRoutesToCache(): void
    {
        $base = sys_get_temp_dir() . '/omegarc-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        Router::reset();

        $command = new RouteCacheCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('No routes to cache.', $result->getDisplay());

        Router::reset();
        $app->flush();
    }

    public function testIgnoresANonArrayFilesOption(): void
    {
        $base = sys_get_temp_dir() . '/omegarc-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        Router::reset();

        $command = new RouteCacheCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--files' => 'not-an-array']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('No routes to cache.', $result->getDisplay());

        Router::reset();
        $app->flush();
    }

    public function testCreatesTheRouteCacheFileForClosureAndCallableRoutes(): void
    {
        $base = sys_get_temp_dir() . '/omegarc-' . bin2hex(random_bytes(4));
        mkdir($base . '/bootstrap/cache', 0777, true);

        $app = new Application($base);

        Router::reset();
        Router::get('/home', static function (): string {
            return 'home';
        });
        Router::post('/users', 'HomeController@store');

        $command = new RouteCacheCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $cachePath = $app->getApplicationCachePath() . 'route.php';

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Route cache file has been successfully created.', $result->getDisplay());
        $this->assertFileExists($cachePath);

        $content = (string) file_get_contents($cachePath);

        $this->assertStringContainsString("'uri' => '/home'", $content);
        $this->assertStringContainsString("'uri' => '/users'", $content);
        $this->assertStringContainsString('HomeController@store', $content);

        Router::reset();
        $app->flush();
    }

    public function testRequiresTheGivenRouteFilesBeforeCaching(): void
    {
        $base = sys_get_temp_dir() . '/omegarc-' . bin2hex(random_bytes(4));
        mkdir($base . '/bootstrap/cache', 0777, true);
        file_put_contents(
            $base . '/custom-routes.php',
            '<?php \Omega\Router\Router::get(\'/api\', static function (): void {});'
        );

        $app = new Application($base);

        Router::reset();

        $command = new RouteCacheCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--files' => ['custom-routes.php']]);

        $content = (string) file_get_contents($app->getApplicationCachePath() . 'route.php');

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Route cache file has been successfully created.', $result->getDisplay());
        $this->assertStringContainsString("'uri' => '/api'", $content);

        Router::reset();
        $app->flush();
    }

    public function testRequiresEachRouteFileOnlyOnce(): void
    {
        $base = sys_get_temp_dir() . '/omegarc-' . bin2hex(random_bytes(4));
        mkdir($base . '/bootstrap/cache', 0777, true);
        file_put_contents(
            $base . '/custom-routes.php',
            '<?php \Omega\Router\Router::get(\'/api\', static function (): void {});'
        );

        $app = new Application($base);

        Router::reset();

        $command = new RouteCacheCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([
            '--files' => ['custom-routes.php', 'custom-routes.php'],
        ]);

        $content = (string) file_get_contents($app->getApplicationCachePath() . 'route.php');

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString("'uri' => '/api'", $content);

        Router::reset();
        $app->flush();
    }

    public function testReportsAnErrorWhenAGivenRouteFileDoesNotExist(): void
    {
        $base = sys_get_temp_dir() . '/omegarc-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        Router::reset();

        $command = new RouteCacheCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--files' => ['missing-routes.php']]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            "Route file can't be loaded: 'missing-routes.php'",
            $result->getDisplay()
        );

        Router::reset();
        $app->flush();
    }

    public function testReportsAnErrorWhenAGivenRouteFileThrows(): void
    {
        $base = sys_get_temp_dir() . '/omegarc-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);
        file_put_contents($base . '/boom.php', '<?php throw new RuntimeException(\'Boom\');');

        $app = new Application($base);

        Router::reset();

        $command = new RouteCacheCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--files' => ['boom.php']]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Failed to load route file: Boom', $result->getDisplay());

        Router::reset();
        $app->flush();
    }

    public function testReportsAnErrorWhenThePathBaseBindingIsNotAString(): void
    {
        $base = sys_get_temp_dir() . '/omegarc-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->set('path.base', 123);

        Router::reset();

        $command = new RouteCacheCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--files' => ['routes.php']]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'The "path.base" binding must resolve to a string path.',
            $result->getDisplay()
        );

        Router::reset();
        $app->flush();
    }

    public function testContinuesWhenAGivenFileEntryIsNotAString(): void
    {
        $base = sys_get_temp_dir() . '/omegarc-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        Router::reset();

        $command = new RouteCacheCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--files' => [123]]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('No routes to cache.', $result->getDisplay());

        Router::reset();
        $app->flush();
    }

    public function testReportsAnErrorWhenTheRouteCacheFileCannotBeWritten(): void
    {
        $base = sys_get_temp_dir() . '/omegarc-' . bin2hex(random_bytes(4));
        mkdir($base . '/bootstrap', 0777, true);
        touch($base . '/bootstrap/cache');

        $app = new Application($base);

        Router::reset();
        Router::get('/home', static function (): string {
            return 'home';
        });

        $command = new RouteCacheCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run([]);

        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Failed to build route cache.', $result->getDisplay());

        Router::reset();
        $app->flush();
    }
}
