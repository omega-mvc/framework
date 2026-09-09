<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\Attribute\AsCommand;
use Omega\Database\Facades\DB;
use Omega\DocBlockGenerator\Generate;
use Omega\DocBlockGenerator\Property;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Throwable;

use function file_exists;
use function file_put_contents;
use function is_string;
use function Omega\Application\path;
use function strtolower;
use function ucfirst;

#[AsCommand(
    name: 'make:model',
    description: 'Generates a new model class representing a database table',
    arguments: [
        'name' => [InputArgument::REQUIRED, 'The name of the model']
    ],
    options: [
        'table-name' => ['t', InputOption::VALUE_REQUIRED, 'Set table column when creating model'],
        'force'      => ['f', InputOption::VALUE_NONE, 'Force to create template even if it exists']
    ]
)]
final class MakeModelCommand extends AbstractMakeCommand
{
    public function __invoke(): int
    {
        $this->io->info('Making model file...');

        $name = $this->getArgument('name');

        if (!is_string($name) || trim($name) === '') {
            $this->io->error('The "name" argument must be a non-empty string.');
            return self::FAILURE;
        }

        $name = ucfirst($name);

        $modelPath = $this->app->get('path.model');

        if (!is_string($modelPath)) {
            $this->io->error("The \"path.model\" binding must resolve to a string path.");
            return self::FAILURE;
        }

        $modelLocation = $modelPath . $name . '.php';

        if (file_exists($modelLocation) && !$this->getOption('force')) {
            $this->io->warning('File already exists.');
            $this->io->error('Failed to create model file. Use --force to overwrite.');
            return self::FAILURE;
        }

        $this->io->info("Creating Model class in {$modelLocation}");

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
        $tableName  = strtolower($name);

        if ($this->getOption('table-name')) {
            $optionTableName = $this->getOption('table-name');
            $tableName = is_string($optionTableName) ? $optionTableName : $tableName;
            $this->io->info("Getting information from table [{$tableName}]...");

            try {
                $tableInfo = DB::table($tableName)->info();

                foreach ($tableInfo as $column) {
                    $columnName = $column['COLUMN_NAME'] ?? null;

                    if (!is_string($columnName)) {
                        continue;
                    }

                    $class->addComment('@property mixed $' . $columnName);

                    if ('PRI' === ($column['COLUMN_KEY'] ?? '')) {
                        $primaryKey = $columnName;
                    }
                }
            } catch (Throwable $th) {
                $this->io->warning("Database warning: " . $th->getMessage());
            }
        }

        $class->addProperty('tableName')
            ->visibility(Property::PROTECTED_)
            ->dataType('string')
            ->expecting(" = '{$tableName}'");

        $class->addProperty('primaryKey')
            ->visibility(Property::PROTECTED_)
            ->dataType('string')
            ->expecting(" = '{$primaryKey}'");

        if (file_put_contents($modelLocation, $class->generate()) === false) {
            $this->io->error('Failed to write model file to disk.');
            return self::FAILURE;
        }

        $displayPath = path('app.Models') . $name;
        $this->io->success("Model [{$displayPath}] created successfully.");

        return self::SUCCESS;
    }
}
