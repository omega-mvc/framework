<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

use function is_string;
use function Omega\Application\os_detect;
use function shell_exec;

/**
 * ServeCommand
 *
 * Start PHP built-in development server for the Omega application.
 */
#[AsCommand(
    name: 'serve',
    description: 'Serve the application using the PHP built-in development server',
    options: [
        'host'   => [null, InputOption::VALUE_REQUIRED, 'The host address to serve the application on', '127.0.0.1'],
        'port'   => [null, InputOption::VALUE_REQUIRED, 'The port to serve the application on', 8000],
        'expose' => [null, InputOption::VALUE_NONE, 'Make the server run on the public network'],
    ]
)]
final class ServeCommand extends AbstractCommand
{
    public function __invoke(): int
    {
        $host   = is_string($this->getOption('host')) ? $this->getOption('host') : '127.0.0.1';
        $port   = $this->getOption('port');
        $expose = (bool) $this->getOption('expose');

        if (!is_numeric($port) || (int) $port < 1 || (int) $port > 65535) {
            $this->io->error('The port must be an integer between 1 and 65535.');
            return self::FAILURE;
        }

        $port = (int) $port;

        if (@fsockopen('127.0.0.1', $port)) {
            $this->io->error("The port {$port} is already in use.");
            return self::FAILURE;
        }

        $this->launchServer($host, $port, $expose);

        return self::SUCCESS;
    }

    /**
     * Launch the PHP built-in server.
     *
     * @param string $host The host address to bind.
     * @param int $port The port to serve the application on.
     * @param bool $expose Whether to expose the server on the public network.
     * @return void
     */
    private function launchServer(string $host, int $port, bool $expose): void
    {
        $address = $expose ? '0.0.0.0' : $host;

        $this->io->title('Server running at:');

        $this->io->writeln(sprintf('Local:   <info>http://%s:%d</info>', $expose ? '127.0.0.1' : $address, $port));

        if ($expose) {
            $hostname = gethostname();
            $this->io->writeln(sprintf('Network: <info>http://%s:%d</info>', $hostname !== false ? $hostname : '127.0.0.1', $port));
        }

        $this->io->newLine(2);
        $this->io->writeln('Press <comment>ctrl+c</comment> to stop the server');
        $this->io->info('Server running...');

        // Use pcntl signals if the OS is not Windows
        if (os_detect() !== 'windows' && function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, function (): void {
                $this->io->warning('Server stopped by user');
                exit(self::SUCCESS);
            });

            pcntl_signal(SIGTERM, function (): void {
                $this->io->warning('Server stopped');
                exit(self::SUCCESS);
            });
        }

        $publicPath = $this->app->get('path.public');
        if (!is_string($publicPath)) {
            $this->io->error('The "path.public" binding must resolve to a string path.');
            return;
        }

        shell_exec(
            'php -dxdebug.mode=off -S ' . $address . ':' . $port
            . ' -t ' . escapeshellarg($publicPath)
        );
    }
}