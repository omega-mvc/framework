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

use DateInvalidTimeZoneException;
use DateMalformedStringException;
use Exception;
use Omega\Console\Commands\MakeCommand;
use Psr\Container\ContainerExceptionInterface;

use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function Omega\Console\error;
use function Omega\Console\info;
use function Omega\Console\success;
use function Omega\Console\text;
use function Omega\Console\warn;
use function Omega\Support\get_path;
use function Omega\Support\slash;
use function Omega\Time\now;
use function str_replace;

/**
 * Generates a new database migration file for a specified table.
 *
 * This command creates a migration stub with a timestamped filename,
 * replacing placeholders with the provided table name. It supports both
 * creating new migrations and updating existing tables when the update
 * option is specified. The generated file is ready for execution to
 * modify the database schema.
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
final class MakeMigrationCommand extends MakeCommand
{
    /**
     * Generates a new migration file.
     *
     * @return int Exit code: 0 on success, 1 on failure
     * @throws DateInvalidTimeZoneException Thrown when a provided timezone is invalid.
     * @throws DateMalformedStringException Thrown when a date string cannot be parsed correctly.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws Exception
     */
    public function makeMigration(): int
    {
        info('Making migration')->out(false);

        $name = $this->option[0] ?? false;
        if (false === $name) {
            warn('Table name cant be empty.')->out(false);
            do {
                $name = text('Fill the table name?', static fn ($text) => $text);
            } while ($name === '' || $name === false);
        }

        $name       = strtolower($name);
        $pathToFile = get_path('path.migration');
        $bath       = now()->format('Y_m_d_His');
        $fileName   = "{$pathToFile}{$bath}_{$name}.php";

        $use      = $this->update ? 'migration_update' : 'migration';
        $template = file_get_contents(slash(path: dirname(__DIR__) . '/stubs/') . $use);
        $template = str_replace('__table__', $name, $template);

        if (false === file_exists($pathToFile) || false === file_put_contents($fileName, $template)) {
            error('Can\'t create migration file.')->out();

            return 1;
        }
        success('Success create migration file.')->out();

        return 0;
    }
}
