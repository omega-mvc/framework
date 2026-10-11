<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Application\Application;
use Omega\Console\Commands\QueueWorkCommand;
use Omega\Facade\AbstractFacade;
use Omega\Queue\Facade\Queue;
use Omega\Queue\QueueManager;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Commands\Fixtures\FakeQueueAdapter;
use Tests\TestCase;

#[CoversClass(Application::class)]
#[CoversClass(Queue::class)]
#[CoversClass(QueueManager::class)]
#[CoversClass(QueueWorkCommand::class)]
final class QueueWorkCommandTest extends TestCase
{
    /**
     * @return array{0: Application, 1: FakeQueueAdapter}
     */
    private function queueWorkApp(): array
    {
        $base = sys_get_temp_dir() . '/omegaqw-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app     = new Application($base);
        $adapter = new FakeQueueAdapter();
        $app->set('queue', new QueueManager('database', $adapter));

        return [$app, $adapter];
    }

    public function testFailsWhenTheQueueManagerIsNotBound(): void
    {
        $base = sys_get_temp_dir() . '/omegaqw-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        $app     = new Application($base);
        $command = new QueueWorkCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('Queue is not set yet.', $result->getDisplay());

        $app->flush();
    }

    public function testExitsImmediatelyWhenTheWorkerFlagIsAlreadySet(): void
    {
        [$app] = $this->queueWorkApp();

        AbstractFacade::flushInstance();
        Queue::setFacadeBase($app);

        $command = new QueueWorkCommand();
        $command->app = $app;

        (new ReflectionClass($command))->getProperty('shouldExit')->setValue($command, true);

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Omega Queue Worker', $result->getDisplay());
        $this->assertStringContainsString('Processing jobs from the queue...', $result->getDisplay());
        $this->assertStringContainsString('Press CTRL+C to stop the worker.', $result->getDisplay());

        $app->flush();
    }

    public function testProcessesAJobAndDeletesItOnSuccess(): void
    {
        if (!function_exists('pcntl_async_signals') || !function_exists('posix_kill')) {
            self::markTestSkipped('Requires the pcntl extension to stop the worker loop.');
        }

        [$app, $adapter] = $this->queueWorkApp();

        AbstractFacade::flushInstance();
        Queue::setFacadeBase($app);

        $adapter->push(function (): void {
            posix_kill(posix_getpid(), SIGINT);
        });

        $command = new QueueWorkCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Processing job #1 from queue "default"', $result->getDisplay());
        $this->assertStringContainsString('Done!', $result->getDisplay());
        $this->assertCount(1, $adapter->getDeletedJobs());

        $app->flush();
    }

    public function testMarksAJobAsFailedWhenProcessingThrows(): void
    {
        if (!function_exists('pcntl_async_signals') || !function_exists('posix_kill')) {
            self::markTestSkipped('Requires the pcntl extension to stop the worker loop.');
        }

        [$app, $adapter] = $this->queueWorkApp();

        AbstractFacade::flushInstance();
        Queue::setFacadeBase($app);

        $adapter->push(function (): void {
            posix_kill(posix_getpid(), SIGINT);

            throw new RuntimeException('boom');
        });

        $command = new QueueWorkCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Failed: boom', $result->getDisplay());
        $this->assertCount(1, $adapter->getFailedJobs());

        $app->flush();
    }
}
