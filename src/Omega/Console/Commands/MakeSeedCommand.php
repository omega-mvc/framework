<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\DocBlockGenerator\Generate;
use Omega\DocBlockGenerator\Method;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

use function file_exists;
use function file_put_contents;
use function is_string;

#[AsCommand(
    name: 'make:seeder',
    description: 'Create a new seeder class',
    arguments: [
        'name'   => [InputArgument::REQUIRED, 'The name of the seeder']
    ],
    options: [
        'force' => ['f', InputOption::VALUE_NONE, 'Overwrite the seeder if it exists']
    ]
)]
final class MakeSeedCommand extends AbstractCommand
{
    public function __invoke(): int
    {
        $name = $this->getArgument('name');
        $filePath = $this->app->get('path.seeder');

        if (!is_string($name)) {
            $this->io->error('The "name" argument must be a string.');
            return self::FAILURE;
        }

        if (!is_string($filePath)) {
            $this->io->error("The \"path.seeder\" binding must resolve to a string path.");
            return self::FAILURE;
        }

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
