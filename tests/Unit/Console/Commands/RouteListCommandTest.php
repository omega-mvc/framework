<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\RouteListCommand;
use Omega\Router\Router;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

#[CoversClass(RouteListCommand::class)]
final class RouteListCommandTest extends TestCase
{
    public function testReportsWhenThereAreNoRoutes(): void
    {
        $base = sys_get_temp_dir() . '/omegarls-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        Router::reset();

        $command = new RouteListCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('No routes found.', $result->getDisplay());

        Router::reset();
        $app->flush();
    }

    public function testListsTheRegisteredRoutes(): void
    {
        $base = sys_get_temp_dir() . '/omegarls-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        Router::reset();
        Router::get('/home', static function (): string {
            return 'home';
        })->name('home');
        Router::delete('/posts/(:id)', static function (): string {
            return 'posts';
        });

        $command = new RouteListCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('GET|HEAD', $display);
        $this->assertStringContainsString('DELETE', $display);
        $this->assertStringContainsString('/home', $display);
        $this->assertStringContainsString('/posts/(:id)', $display);
        $this->assertStringContainsString('home', $display);
        $this->assertStringContainsString('Showing [2] routes.', $display);

        Router::reset();
        $app->flush();
    }
}
