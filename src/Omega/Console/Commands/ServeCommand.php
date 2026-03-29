<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Omega\Support\os_detect;
use function shell_exec;

/**
 * ServeCommand
 *
 * Start PHP built-in development server for the Omega application.
 */
#[AsCommand(
    name: 'serve',
    description: 'Serve server with port number (default 8000)'
)]
class ServeCommand extends AbstractCommand
{
    protected int $port = 8000;

    protected bool $expose = false;

    protected function handle(): int
    {
        $io = new SymfonyStyle($this->input, $this->output);

        // Get options
        $this->port = (int) $this->option('port');
        $this->expose = (bool) $this->option('expose');

        if (!is_numeric($this->port)) {
            $io->error("Port must be numeric");
            return 1;
        }

        $this->launchServer($io, $this->port, $this->expose);

        return 0;
    }

    /**
     * Launch the PHP built-in server.
     */
    private function launchServer(SymfonyStyle $io, int $port, bool $expose): void
    {
        $localIP = gethostbyname(gethostname());

        $io->title('Server running at:');

        // Localhost URL
        $io->writeln('Local:   ' . sprintf('<info>http://localhost:%d</info>', $port));

        // Optional network exposure
        if ($expose) {
            $io->writeln('Network: ' . sprintf('<info>http://%s:%d</info>', $localIP, $port));
        }

        $io->newLine(2);
        $io->writeln('Press <comment>ctrl+c</comment> to stop server');
        $io->success('Server running...');

        // Use pcntl signals if OS is not Windows
        if (os_detect() !== 'windows' && function_exists('pcntl_async_signals')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, function () use ($io) {
                $io->warning('Server stopped by user');
                exit(0);
            });
        }

        $address = $expose ? '0.0.0.0' : '127.0.0.1';
        shell_exec("php -dxdebug.mode=off -S " . $address . ":" . $port . " -t public/");
    }

    protected function configure(): void
    {
        // Symfony 8.x allows attributes but options still declared here
        $this
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'Serve with custom port', 8000)
            ->addOption('expose', null, InputOption::VALUE_NONE, 'Make server run on public network');
    }
}
