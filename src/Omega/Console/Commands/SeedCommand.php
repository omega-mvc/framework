<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use RuntimeException;
use Throwable;

use function is_string;

#[AsCommand(
    name: 'db:seed',
    description: 'Seed the database with records',
    options: [
        'class'      => ['c', InputOption::VALUE_OPTIONAL, 'Target class (will add `Database\\Seeders\\`)'],
        'name-space' => ['s', InputOption::VALUE_OPTIONAL, 'Target class with full namespace'],
        'force'      => ['f', InputOption::VALUE_NONE, 'Force the operation to run when in production']

    ]
)]
final class SeedCommand extends AbstractCommand
{
    public function __invoke(): int
    {
        if (!$this->confirmToProceed()) {
            return self::FAILURE;
        }

        $class = is_string($this->getOption('class')) ? $this->getOption('class') : null;
        $namespace = is_string($this->getOption('name-space')) ? $this->getOption('name-space') : null;

        if ($class && $namespace) {
            $this->io->warning('Use only one: --class or --name-space, be specific.');
            return self::FAILURE;
        }

        // Logica di risoluzione della classe come nel tuo originale
        $targetClass = match (true) {
            $namespace !== null => $namespace,
            $class !== null => "Database\\Seeders\\{$class}",
            default => "Database\\Seeders\\DatabaseSeeder",
        };

        if (!class_exists($targetClass)) {
            $this->io->error("Seeder class [{$targetClass}] does not exist.");
            return self::FAILURE;
        }

        $this->io->info("Running seeder: {$targetClass}");

        try {
            $seeder = $this->app->make($targetClass);

            if (!is_object($seeder)) {
                throw new RuntimeException('Unable to resolve the seeder class to an object.');
            }

            $this->app->call([$seeder, 'run']);

            $this->io->success("Success run seeder: {$targetClass}");
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->io->error("Seeding failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function confirmToProceed(): bool
    {
        if ($this->app->isDev() || $this->getOption('force')) {
            return true;
        }

        $helper = $this->getHelper('question');

        if (!$helper instanceof QuestionHelper) {
            return false;
        }

        $question = new ConfirmationQuestion('Running seeder in production? (y/n) ', false);

        return (bool) $helper->ask($this->input, $this->output, $question);
    }
}
