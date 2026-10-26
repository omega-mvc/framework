<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\SeedCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\FakeQuestionHelper;
use Tests\Console\Commands\Fixtures\FakeSeeder;
use Tests\TestCase;

#[CoversClass(SeedCommand::class)]
final class SeedCommandTest extends TestCase
{
    public function testAbortsTheSeederWhenTheProductionQuestionIsDeclined(): void
    {
        $base = sys_get_temp_dir() . '/omegaseed-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new SeedCommand();
        $command->app = $app;
        $command->setHelperSet(new HelperSet([new QuestionHelper()]));

        $result = (new CommandTester($command))->run([], ['n'], true);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Running seeder in production? (y/n)', $result->getDisplay());

        $app->flush();
    }

    public function testProceedsWhenTheProductionQuestionIsConfirmed(): void
    {
        $base = sys_get_temp_dir() . '/omegaseed-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new SeedCommand();
        $command->app = $app;
        $command->setHelperSet(new HelperSet([new QuestionHelper()]));

        $result = (new CommandTester($command))->run(['--class' => 'Ghost'], ['y'], true);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Running seeder in production? (y/n)', $result->getDisplay());
        $this->assertStringContainsString(
            'Seeder class [Database\Seeders\Ghost] does not exist.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testSkipsTheProductionQuestionWhenForced(): void
    {
        $base = sys_get_temp_dir() . '/omegaseed-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new SeedCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--force' => true, '--class' => 'Ghost']);

        $display = $result->getDisplay();

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringNotContainsString('Running seeder in production? (y/n)', $display);
        $this->assertStringContainsString('Seeder class [Database\Seeders\Ghost] does not exist.', $display);

        $app->flush();
    }

    public function testAbortsWhenTheQuestionHelperIsNotAvailable(): void
    {
        $base = sys_get_temp_dir() . '/omegaseed-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new SeedCommand();
        $command->app = $app;
        $command->setHelperSet(new HelperSet([new FakeQuestionHelper()]));

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringNotContainsString('Running seeder in production? (y/n)', $result->getDisplay());

        $app->flush();
    }

    public function testReportsWhenTheSeederCannotBeResolvedToAnObject(): void
    {
        $base = sys_get_temp_dir() . '/omegaseed-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);
        $app->bind(FakeSeeder::class, static fn (): int => 42);

        $command = new SeedCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([
            '--force'      => true,
            '--name-space' => FakeSeeder::class,
        ]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'Seeding failed: Unable to resolve the seeder class to an object.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testRejectsBothClassAndNameSpaceOptionsAtTheSameTime(): void
    {
        $base = sys_get_temp_dir() . '/omegaseed-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new SeedCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([
            '--force'      => true,
            '--class'      => 'ExampleSeeder',
            '--name-space' => FakeSeeder::class,
        ]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'Use only one: --class or --name-space, be specific.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testReportsWhenTheDefaultSeederClassDoesNotExist(): void
    {
        $base = sys_get_temp_dir() . '/omegaseed-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app = new Application($base);

        $command = new SeedCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--force' => true]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'Seeder class [Database\Seeders\DatabaseSeeder] does not exist.',
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testRunsTheTargetSeederWhenForced(): void
    {
        $base = sys_get_temp_dir() . '/omegaseed-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        FakeSeeder::$runs = 0;
        FakeSeeder::$throw = null;

        $app = new Application($base);

        $command = new SeedCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([
            '--force'      => true,
            '--name-space' => FakeSeeder::class,
        ]);

        $display = $result->getDisplay();

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Running seeder: ' . FakeSeeder::class, $display);
        $this->assertStringContainsString('Success run seeder: ' . FakeSeeder::class, $display);
        $this->assertSame(1, FakeSeeder::$runs);

        $app->flush();
    }

    public function testReportsWhenTheSeederRunFails(): void
    {
        $base = sys_get_temp_dir() . '/omegaseed-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        FakeSeeder::$runs = 0;
        FakeSeeder::$throw = new RuntimeException('Boom');

        $app = new Application($base);

        $command = new SeedCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([
            '--force'      => true,
            '--name-space' => FakeSeeder::class,
        ]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Seeding failed: Boom', $result->getDisplay());

        FakeSeeder::$throw = null;

        $app->flush();
    }
}
