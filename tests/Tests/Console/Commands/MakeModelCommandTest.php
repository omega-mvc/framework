<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\MakeModelCommand;
use Omega\Facade\AbstractFacade;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\ScriptedConnection;
use Tests\TestCase;

#[CoversClass(MakeModelCommand::class)]
final class MakeModelCommandTest extends TestCase
{
    /**
     * @return array{0: Application, 1: string}
     */
    private function makeModelApp(): array
    {
        $base = sys_get_temp_dir() . '/omegamodel-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $save = $base . '/models/';
        mkdir($save, 0777, true);

        $app = new Application($base);
        $app->set('path.model', $save);

        return [$app, $save];
    }

    public function testCreatesAModelFileForMakeModel(): void
    {
        [$app, $save] = $this->makeModelApp();

        $command = new MakeModelCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        $content = (string) file_get_contents($save . 'UserProfile.php');

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Making model file...', $result->getDisplay());
        $this->assertStringContainsString(
            'Model [app/Models/UserProfile] created successfully.',
            $result->getDisplay()
        );
        $this->assertFileExists($save . 'UserProfile.php');
        $this->assertStringContainsString('namespace App\\Models;', $content);
        $this->assertStringContainsString('extends Model', $content);
        $this->assertStringContainsString("= 'userprofile'", $content);

        $app->flush();
    }

    public function testRejectsAnEmptyNameArgumentForMakeModel(): void
    {
        [$app] = $this->makeModelApp();

        $command = new MakeModelCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => '   ']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The "name" argument must be a non-empty string.', $result->getDisplay());

        $app->flush();
    }

    public function testFailsWhenThePathModelBindingIsNotAString(): void
    {
        [$app] = $this->makeModelApp();
        $app->set('path.model', 123);

        $command = new MakeModelCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'The "path.model" binding must resolve to a string path.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testReportsThatTheModelFileAlreadyExistsWithoutForce(): void
    {
        [$app, $save] = $this->makeModelApp();
        file_put_contents($save . 'UserProfile.php', 'x');

        $command = new MakeModelCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('File already exists.', $result->getDisplay());
        $this->assertStringContainsString(
            'Failed to create model file. Use --force to overwrite.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testOverwritesAnExistingModelFileWithForce(): void
    {
        [$app, $save] = $this->makeModelApp();
        file_put_contents($save . 'UserProfile.php', 'old');

        $command = new MakeModelCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile', '--force' => true]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringNotContainsString('old', (string) file_get_contents($save . 'UserProfile.php'));

        $app->flush();
    }

    public function testFailsWhenTheModelFileCannotBeWritten(): void
    {
        $base = sys_get_temp_dir() . '/omegamodel-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $blocker = $base . 'blocker';
        touch($blocker);
        $app->set('path.model', $blocker . '/models/');

        $command = new MakeModelCommand();
        $command->app = $app;

        set_error_handler(static fn (int $severity, string $message): bool => true);

        $result = (new CommandTester($command))->run(['name' => 'UserProfile']);

        restore_error_handler();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Failed to write model file to disk.', $result->getDisplay());

        $app->flush();
    }

    public function testGeneratesTableColumnsAsDocblockPropertiesWithTableName(): void
    {
        ScriptedConnection::resetShared();

        [$app, $save] = $this->makeModelApp();

        $connection = ScriptedConnection::shared('model-info')
            ->whenQueryContains('INFORMATION_SCHEMA', [
                ['COLUMN_NAME' => 'id', 'COLUMN_KEY' => 'PRI'],
                ['COLUMN_NAME' => 'email', 'COLUMN_KEY' => ''],
            ]);

        $app->set('database', $connection);
        AbstractFacade::setFacadeBase($app);

        $command = new MakeModelCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile', '--table-name' => 'users']);

        $content = (string) file_get_contents($save . 'UserProfile.php');

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Getting information from table [users]...', $result->getDisplay());
        $this->assertStringContainsString('@property mixed $id', $content);
        $this->assertStringContainsString('@property mixed $email', $content);
        $this->assertStringContainsString("= 'users'", $content);
        $this->assertStringContainsString("= 'id'", $content);

        $app->flush();
    }

    public function testSkipsColumnsWithoutAStringName(): void
    {
        ScriptedConnection::resetShared();

        [$app, $save] = $this->makeModelApp();

        $connection = ScriptedConnection::shared('model-skip')
            ->whenQueryContains('INFORMATION_SCHEMA', [
                ['COLUMN_NAME' => 123, 'COLUMN_KEY' => 'PRI'],
                ['COLUMN_NAME' => 'email', 'COLUMN_KEY' => ''],
            ]);

        $app->set('database', $connection);
        AbstractFacade::setFacadeBase($app);

        $command = new MakeModelCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile', '--table-name' => 'users']);

        $content = (string) file_get_contents($save . 'UserProfile.php');

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('@property mixed $email', $content);
        $this->assertStringNotContainsString('@property mixed $123', $content);
        $this->assertStringContainsString("= 'id'", $content);

        $app->flush();
    }

    public function testWarnsAndContinuesWhenReadingTableInformationFails(): void
    {
        ScriptedConnection::resetShared();

        [$app, $save] = $this->makeModelApp();

        $connection = ScriptedConnection::shared('model-fail')
            ->failingOn('INFORMATION_SCHEMA');

        $app->set('database', $connection);
        AbstractFacade::setFacadeBase($app);

        $command = new MakeModelCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['name' => 'UserProfile', '--table-name' => 'users']);

        $content = (string) file_get_contents($save . 'UserProfile.php');

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Database warning: Scripted SQL failure', $result->getDisplay());
        $this->assertStringContainsString("= 'users'", $content);

        $app->flush();
    }
}
