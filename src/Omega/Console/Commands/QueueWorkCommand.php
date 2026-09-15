<?php

/**
 * Part of Omega - Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Queue\Exception\QueueException;
use Omega\Queue\Facade\Queue;
use Throwable;

use function date;
use function function_exists;
use function is_object;
use function method_exists;
use function microtime;
use function pcntl_async_signals;
use function pcntl_signal;
use function round;
use function sleep;
use function sprintf;

#[AsCommand(
    name: 'queue:work',
    description: 'Process jobs from the queue',
)]
final class QueueWorkCommand extends AbstractCommand
{
    private bool $shouldExit = false;

    public function __invoke(): int
    {
        if (!$this->app->has('queue')) {
            $this->io->error('Queue is not set yet.');

            return self::FAILURE;
        }

        $this->io->title('Omega Queue Worker');
        $this->io->info('Processing jobs from the queue...');
        $this->io->note('Press CTRL+C to stop the worker.');

        if (function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, function (): void {
                $this->shouldExit = true;
            });
        }

        while (!$this->shouldExit) {
            $start = microtime(true);
            $job = Queue::shift();

            if ($job === null) {
                sleep(1);
                continue;
            }

            $this->io->write(sprintf(
                '<fg=gray>[%s]</> Processing job #%d from queue "%s"... ',
                date('Y-m-d H:i:s'),
                $job->getId(),
                $job->getQueue(),
            ));

            try {
                $payload = $job->getRawBody();

                if (is_object($payload) && method_exists($payload, '__invoke')) {
                    $payload();
                } else {
                    throw new QueueException('Unserialized job payload is not invokable.');
                }

                Queue::delete($job);

                $executionTime = round((microtime(true) - $start) * 1000, 2);
                $this->io->writeln("Done! ({$executionTime}ms)");
            } catch (Throwable $e) {
                Queue::failed($job, $e);
                $this->io->writeln("<error>Failed: {$e->getMessage()}</error>");
            }
        }

        return self::SUCCESS;
    }
}
