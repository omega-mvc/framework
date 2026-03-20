<?php

/**
 * Part of Omega - Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Omega\Console\Commands\Make;

use Omega\Console\Commands\MakeCommand;
use Omega\Console\Style\Style;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Support\Facades\DB;
use Omega\Template\Generate;
use Omega\Template\Property;
use ReflectionException;
use Throwable;

use function file_exists;
use function Omega\Console\error;
use function Omega\Console\info;
use function Omega\Console\success;
use function Omega\Console\warn;
use function Omega\Support\get_path;
use function Omega\Support\path;
use function ucfirst;

/**
 * Generates a new model class representing a database table.
 *
 * This command creates a PHP model file with default properties for
 * table name and primary key. If the table exists and the --table-name
 * option is provided, it reads the table schema to add @property
 * annotations for each column and automatically detects the primary key.
 * The generated model extends the base Model class and is ready for
 * use in the application.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Commands\Make
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html  GPL V3.0+
 * @version    2.0.0
 */
final class MakeModelCommand extends MakeCommand
{
    /**
     * Generates a new model class.
     *
     * @return int Exit code: 0 on success, 1 on failure
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     * @throws Throwable Thrown when an unexpected error occurs while retrieving table information
     *                   or generating the model class. This includes any exception thrown
     *                   by database operations or file system access.
     */
    public function makeModel(): int
    {
        info('Making model file...')->out(false);

        $this->isPath('path.model');

        $name          = ucfirst($this->option[0]);
        $modelLocation = get_path('path.model') . $name . '.php';

        if (file_exists($modelLocation) && false === $this->option('force', false)) {
            warn('File already exist')->out(false);
            error('Failed Create model file')->out();

            return 1;
        }

        info('Creating Model class in ' . $modelLocation)->out(false);

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
        $tableName  = $this->option[0];
        if ($this->option('table-name', false)) {
            $tableName = $this->option('table-name');
            info("Getting Information from table {$tableName}.")->out(false);
            try {
                foreach (DB::table($tableName)->info() as $column) {
                    $class->addComment('@property mixed $' . $column['COLUMN_NAME']);
                    if ('PRI' === $column['COLUMN_KEY']) {
                        $primaryKey = $column['COLUMN_NAME'];
                    }
                }
            } catch (Throwable $th) {
                warn($th->getMessage())->out(false);
            }
        }

        $class->addProperty('tableName')
            ->visibility(Property::PROTECTED_)
            ->dataType('string')
            ->expecting(" = '{$tableName}'");
        $class->addProperty('primaryKey')
            ->visibility(Property::PROTECTED_)
            ->dataType('string')
            ->expecting("= '{$primaryKey}'");

        if (false === file_put_contents($modelLocation, $class->generate())) {
            error('Failed Create model file')->out();

            return 1;
        }

        $path = path('app.Models') . $name;

        success('Model [' . new Style($path)->bold() . '] create successfully.')->out();

        return 0;
    }
}
