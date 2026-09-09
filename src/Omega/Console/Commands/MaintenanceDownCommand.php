<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

use function Omega\Application\slash;
use function is_string;

#[AsCommand(
    name: 'down',
    description: 'Put the application into maintenance mode',
    options: [
        'redirect' => [null, InputOption::VALUE_REQUIRED, 'The path that users should be redirected to'],
        'retry'    => [null, InputOption::VALUE_REQUIRED, 'The number of seconds after which the request may be retried'],
        'status'   => [null, InputOption::VALUE_REQUIRED, 'The status code that should be returned', 503],
        'template' => [null, InputOption::VALUE_REQUIRED, 'The template to display while in maintenance mode'],
    ]
)]
final class MaintenanceDownCommand extends AbstractCommand
{
    public function __invoke(): int
    {
        if ($this->app->isDownMaintenanceMode()) {
            $this->io->warning('Application is already under maintenance mode.');
            return self::FAILURE;
        }

        $status = $this->getOption('status');
        $retry  = $this->getOption('retry');

        if (!is_numeric($status) || (int) $status < 100 || (int) $status > 599) {
            $this->io->error('The status code must be an integer between 100 and 599.');
            return self::FAILURE;
        }

        if ($retry !== null && (!is_numeric($retry) || (int) $retry < 0)) {
            $this->io->error('The retry value must be an integer greater than or equal to zero.');
            return self::FAILURE;
        }

        $stubPath = slash(dirname(__DIR__) . '/stubs/');

        $downStub        = file_get_contents($stubPath . 'down.stub');
        $maintenanceStub = file_get_contents($stubPath . 'maintenance.stub');

        if ($downStub === false || $maintenanceStub === false) {
            $this->io->error('Unable to read the maintenance mode stub files.');
            return self::FAILURE;
        }

        $storagePath = $this->app->get('path.storage');
        if (!is_string($storagePath)) {
            $this->io->error('The "path.storage" binding must resolve to a string path.');
            return self::FAILURE;
        }

        $storagePath .= 'app/';

        if (false === file_put_contents($storagePath . 'down', $this->buildDown((string) $downStub, (int) $status))) {
            $this->io->error('Unable to write the maintenance mode configuration.');
            return self::FAILURE;
        }

        if (false === file_put_contents($storagePath . 'maintenance.php', $maintenanceStub)) {
            $this->io->error('Unable to write the maintenance mode file.');
            return self::FAILURE;
        }

        $this->io->info('Application is now in maintenance mode.');

        return self::SUCCESS;
    }

    /**
     * Fill the down configuration stub with the provided options.
     *
     * @param string $stub The raw down stub contents.
     * @param int $status The status code to return while in maintenance mode.
     * @return string The compiled down configuration.
     */
    private function buildDown(string $stub, int $status): string
    {
        $data = [
            'redirect' => $this->getOption('redirect'),
            'retry'    => $this->getOption('retry'),
            'status'   => $status,
            'template' => $this->getOption('template'),
        ];

        foreach ($data as $key => $value) {
            $stub = str_replace('{{ ' . $key . ' }}', var_export($value, true), $stub);
        }

        return $stub;
    }
}
