<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Omega\Support\Facades\DB;
use Omega\Template\Generate;
use Omega\Template\Property;
use Throwable;

use function Omega\Support\path;
use function ucfirst;
use function file_exists;
use function file_put_contents;

#[AsCommand(
    name: 'make:model',
    description: 'Generates a new model class representing a database table'
)]
final class MakeModelCommand extends AbstractCommand
{
    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the model class')
            ->addOption('table-name', 't', InputOption::VALUE_REQUIRED, 'Set table column when creating model')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force to create template even if it exists');
    }

    protected function handle(): int
    {
        $this->info('Making model file...');

        // 1. Verifica percorso configurato
        $this->isPath('path.model');

        $name = ucfirst($this->argument('name'));
        $modelLocation = $this->app->get('path.model') . $name . '.php';

        // 2. Controllo esistenza file e flag --force
        if (file_exists($modelLocation) && !$this->option('force')) {
            $this->warn('File already exists.');
            $this->error('Failed to create model file. Use --force to overwrite.');
            return self::FAILURE;
        }

        $this->info("Creating Model class in {$modelLocation}");

        // 3. Configurazione del generatore dinamico di Omega
        $class = new Generate($name);
        $class->customizeTemplate(
            "<?php\n\ndeclare(strict_types=1);\n{{before}}{{comment}}\n{{rule}}class\40{{head}}\n{\n{{body}}}{{end}}"
        );
        $class->tabSize(4);
        $class->tabIndent(' ');
        $class->setEndWithNewLine();
        $class->namespace('App\\Models');
        $class->uses(['Omega\Database\Model\Model']);
        $class->extend('Model');

        $primaryKey = 'id';
        $tableName  = strtolower($this->argument('name')); // Default: nome modello minuscolo

        // 4. Introspezione Database (se richiesto --table-name)
        if ($this->option('table-name')) {
            $tableName = $this->option('table-name');
            $this->info("Getting information from table [{$tableName}]...");

            try {
                $tableInfo = DB::table($tableName)->info();

                foreach ($tableInfo as $column) {
                    // Aggiunge le @property nel DocBlock per l'autocompletamento dell'IDE
                    $class->addComment('@property mixed $' . $column['COLUMN_NAME']);

                    // Rilevamento automatico della Primary Key
                    if ('PRI' === ($column['COLUMN_KEY'] ?? '')) {
                        $primaryKey = $column['COLUMN_NAME'];
                    }
                }
            } catch (Throwable $th) {
                // Se la tabella non esiste o ci sono errori DB, avvisiamo ma continuiamo con i default
                $this->warn("Database warning: " . $th->getMessage());
            }
        }

        // 5. Aggiunta proprietà protette alla classe
        $class->addProperty('tableName')
            ->visibility(Property::PROTECTED_)
            ->dataType('string')
            ->expecting(" = '{$tableName}'");

        $class->addProperty('primaryKey')
            ->visibility(Property::PROTECTED_)
            ->dataType('string')
            ->expecting(" = '{$primaryKey}'");

        // 6. Scrittura fisica del file generato
        if (file_put_contents($modelLocation, $class->generate()) === false) {
            $this->error('Failed to write model file to disk.');
            return self::FAILURE;
        }

        $displayPath = path('app.Models') . $name;
        $this->success("Model [{$displayPath}] created successfully.");

        return self::SUCCESS;
    }
}
