<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Throwable;

use function Omega\Support\app;

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

    protected function handle(): int
    {
        if (!$this->confirmToProceed()) {
            return self::FAILURE;
        }

        $class = $this->option('class');
        $namespace = $this->option('name-space');

        // Controllo di mutua esclusività: non possiamo usarli entrambi
        if ($class && $namespace) {
            $this->warn('Use only one: --class or --name-space, be specific.');
            return self::FAILURE;
        }

        // Logica di risoluzione della classe come nel tuo originale
        $targetClass = match (true) {
            $namespace !== null => $namespace,
            $class !== null => "Database\\Seeders\\{$class}",
            default => "Database\\Seeders\\DatabaseSeeder",
        };

        if (!class_exists($targetClass)) {
            $this->error("Seeder class [{$targetClass}] does not exist.");
            return self::FAILURE;
        }

        $this->info("Running seeder: {$targetClass}");

        try {
            $seeder = app()->make($targetClass);
            app()->call([$seeder, 'run']);

            $this->success("Success run seeder: {$targetClass}");
            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error("Seeding failed: " . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function confirmToProceed(): bool
    {
        if (app()->isDev() || $this->option('force')) {
            return true;
        }

        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion('Running seeder in production? (y/n) ', false);

        return $helper->ask($this->input, $this->output, $question);
    }
}
