<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Omega\Template\Generate;
use Omega\Template\Method;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand(
    name: 'db:make',
    description: 'Create a new seeder class',
    arguments: [
        'name'   => [InputArgument::REQUIRED, 'The name of the seeder']
    ],
    options: [
        'force' => ['f', InputOption::VALUE_NONE, 'Overwrite the seeder if it exists']
    ]
)]
final class MakeSeedCommand extends AbstractMakeCommand
{
    public function __invoke(): int
    {
        $name = $this->getArgument('name');
        $filePath = $this->app->get('path.seeder');

        if (file_exists($filePath) && !$this->getOption('force')) {
            $this->io->error("Seeder [{$name}] already exists!");
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
            $this->io->error("Failed to create seeder [{$name}].");
            return self::FAILURE;
        }

        $this->io->success("Seeder [{$name}] created successfully.");
        return self::SUCCESS;
    }
}
