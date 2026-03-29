<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Omega\Template\Generate;
use Omega\Template\Method;

#[AsCommand(
    name: 'db:make',
    description: 'Create a new seeder class'
)]
final class MakeSeedCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the seeder class')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Overwrite the seeder if it exists');
    }

    protected function handle(): int
    {
        $name = $this->argument('name');
        $filePath = $this->app->get('path.seeder', $name . '.php');

        if (file_exists($filePath) && !$this->option('force')) {
            $this->error("Seeder [{$name}] already exists!");
            return self::FAILURE;
        }

        $generator = new Generate($name);
        $generator->tabIndent(' ')
            ->tabSize(4)
            ->namespace('Database\Seeders')
            ->use('Omega\Database\Seeder\AbstractSeeder')
            ->extend('AbstractSeeder')
            ->setEndWithNewLine();

        $generator->addMethod('run')
            ->visibility(Method::PUBLIC_)
            ->setReturnType('void')
            ->body('// Insert your database seeding logic here');

        if (file_put_contents($filePath, $generator->__toString()) === false) {
            $this->error("Failed to create seeder [{$name}].");
            return self::FAILURE;
        }

        $this->success("Seeder [{$name}] created successfully.");
        return self::SUCCESS;
    }
}
