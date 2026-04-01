<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Throwable;

#[AsCommand(
    name: 'db:seed',
    description: 'Seed the database with records'
)]
final class SeedCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addOption('class', 'c', InputOption::VALUE_OPTIONAL, 'Target class (will add `Database\\Seeders\\`)')
            ->addOption('name-space', 's', InputOption::VALUE_OPTIONAL, 'Target class with full namespace')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force the operation to run when in production');
    }

    public function __invoke(): int
    {
        if (!$this->confirmToProceed()) {
            return self::FAILURE;
        }

        $class = $this->getOption('class');
        $namespace = $this->getOption('name-space');

        // Controllo di mutua esclusività: non possiamo usarli entrambi
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
        $question = new ConfirmationQuestion('Running seeder in production? (y/n) ', false);

        return $helper->ask($this->input, $this->output, $question);
    }
}
