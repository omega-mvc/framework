<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Omega\Text\Str;
use Symfony\Component\Console\Input\InputArgument;

use function Omega\Support\path;
use function Omega\Support\slash;

#[AsCommand(
    name: 'make:command',
    description: 'Generate new command class',
    arguments: [
        'name' => [InputArgument::REQUIRED, 'The name of the command']
    ]
)]
final class MakeCommand extends AbstractMakeCommand
{
    public function __invoke(): int
    {
        $this->io->info('Making command file...');
        $this->isPath('path.command');

        $name = $this->getArgument('name');

        // Generiamo il nome kebab-case (es. HelloWorld -> hello-world)
        $kebabName = Str::toKebabCase($name);

        // Passiamo le sostituzioni al template
        $success = $this->makeTemplate($name, [
            'template_location' => slash(path: dirname(__DIR__) . '/stubs/command.stub'),
            'save_location'      => $this->app->get('path.command'),
            'pattern'            => '__command__', // Questo sostituisce la classe
            'suffix'             => 'Command.php',
            // Aggiungiamo una logica per rimpiazzare __name__ nello stub
            'vars'               => [
                '__name__' => $kebabName,
            ]
        ]);

        if (!$success) {
            $this->io->error('Failed to create command file');
            return self::FAILURE;
        }

        $this->registerCommand($name);

        $path = path('app.Console.Commands') . $name . 'Command.php';
        $this->io->success("Command [$path] created successfully.");

        return self::SUCCESS;
    }

    /**
     * Registra il comando nel file config/command.php
     */
    private function registerCommand(string $name): void
    {
        $configPath = $this->app->get('path.config') . 'command.php';

        $content = file_get_contents($configPath);

        $placeholder = '// more commands here';

        $commandName = Str::toKebabCase($name);

        $commandEntry = sprintf(
            "'app:%s' => \\App\\Console\\Commands\\%sCommand::class",
            $commandName,
            $name
        );

        // Evita duplicati
        if (str_contains($content, $commandEntry)) {
            $this->io->warning('Command already registered');

            return;
        }

        $replacement = $commandEntry . ",\n    " . $placeholder;

        $content = str_replace($placeholder, $replacement, $content);

        file_put_contents($configPath, $content);
    }
}
