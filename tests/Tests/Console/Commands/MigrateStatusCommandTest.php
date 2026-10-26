<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MigrateStatusCommand;
use Omega\Facade\AbstractFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\ScriptedConnection;
use Tests\TestCase;

#[CoversClass(MigrateStatusCommand::class)]
final class MigrateStatusCommandTest extends TestCase
{
    public function testPrintsEveryMigrationWithItsBatch(): void
    {
        ScriptedConnection::resetShared();

        $app = new Application(sys_get_temp_dir() . '/omegastatus-' . bin2hex(random_bytes(4)));
        $connection = new ScriptedConnection('omega_test');
        $connection->whenQueryContains('migration', [
            ['migration' => 'create_users_table', 'batch' => 1],
            ['migration' => 'create_posts_table', 'batch' => 2],
        ]);
        $app->set('database', $connection);

        AbstractFacade::setFacadeBase($app);

        $command = new MigrateStatusCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('show migration status', $display);
        $this->assertStringContainsString('create_users_table', $display);
        $this->assertStringContainsString('create_posts_table', $display);

        $app->flush();
    }
}
