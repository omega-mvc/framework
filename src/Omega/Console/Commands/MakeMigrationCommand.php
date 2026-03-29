<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function Omega\Support\slash;
use function Omega\Time\now;

#[AsCommand(
    name: 'make:migration',
    description: 'Generate a new database migration file'
)]
final class MakeMigrationCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'The name of the table')
            ->addOption('update', 'u', InputOption::VALUE_NONE, 'Generate migration file with alter (update)');
    }

    protected function handle(): int
    {
        $this->info('Making migration...');

        // 1. Recupero e validazione Nome (Interattivo se manca)
        $name = $this->argument('name');

        if (!$name) {
            $this->warn('Table name cannot be empty.');
            $name = $this->io->ask('Please fill the table name', null, function ($answer) {
                if (empty($answer)) {
                    throw new \RuntimeException('The table name is required.');
                }
                return $answer;
            });
        }

        $name = strtolower((string)$name);

        // 2. Definizione percorsi e nomi file
        $pathToFile = $this->app->get('path.migration');
        $timestamp  = now()->format('Y_m_d_His');
        $fileName   = "{$pathToFile}{$timestamp}_{$name}.php";

        // 3. Scelta dello Stub
        $stubName = $this->option('update') ? 'migration_update.stub' : 'migration.stub';
        $stubPath = slash(dirname(__DIR__) . '/stubs/') . $stubName;

        if (!file_exists($stubPath)) {
            $this->error("Stub not found at: {$stubPath}");
            return self::FAILURE;
        }

        // 4. Lettura e rimpiazzo
        $template = file_get_contents($stubPath);
        $template = str_replace('__table__', $name, $template);

        // 5. Scrittura file
        if (!is_dir($pathToFile)) {
            mkdir($pathToFile, 0755, true);
        }

        if (file_put_contents($fileName, $template) === false) {
            $this->error("Can't create migration file in: {$pathToFile}");
            return self::FAILURE;
        }

        $this->success("Success! Migration file created: " . basename($fileName));

        return self::SUCCESS;
    }
}
