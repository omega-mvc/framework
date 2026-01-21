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

namespace Omega\Console\Commands;

use Exception;
use Omega\Console\AbstractCommand;
use Omega\Console\Prompt;
use Omega\Console\Traits\PrintHelpTrait;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Template\Generate;
use Omega\Template\Method;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Throwable;

use function class_exists;
use function file_exists;
use function file_put_contents;
use function Omega\Console\error;
use function Omega\Console\info;
use function Omega\Console\style;
use function Omega\Console\success;
use function Omega\Console\warn;

/**
 * SeedCommand
 *
 * Handles database seeding operations from the console.
 * This command allows executing existing seeders to populate the database
 * and generating new seeder classes following the application's conventions.
 *
 * It provides safety checks to prevent accidental execution in production
 * environments, unless explicitly confirmed or forced.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @property string|null $class
 * @property bool|null   $force
 */
class SeedCommand extends AbstractCommand
{
    use PrintHelpTrait;

    /**
     * Command registration configuration.
     *
     * Defines the pattern used to invoke the command and the method to execute.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $command = [
        [
            'pattern' => 'db:seed',
            'fn'      => [self::class, 'main'],
        ], [
            'pattern' => 'make:seed',
            'fn'      => [self::class, 'make'],
        ],
    ];

    /**
     * Returns a description of the command, its options, and their relations.
     *
     * This is used to generate help output for users.
     *
     * @return array<string, array<string, string|string[]>>
     */
    public function printHelp(): array
    {
        return [
            'commands'  => [
                'db:seed'      => 'Run seeding',
                'make:seed'    => 'Create new seeder class',
            ],
            'options'   => [
                '--class'      => 'Target class (will add `Database\\Seeders\\`)',
                '--name-space' => 'Target class with full namespace',
            ],
            'relation'  => [
                'db:seed'      => ['--class', '--name-space'],
            ],
        ];
    }

    /**
     * Determines whether the seeder is allowed to run in the current environment.
     *
     * In development mode the seeder is executed immediately.
     * In production mode, execution requires explicit user confirmation
     * unless the force option is enabled.
     *
     * @return bool Returns true if the seeder execution is allowed in the current environment,
     *              or false if execution is denied by the user.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws Exception Thrown if reading input from STDIN fails during the prompt.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    private function runInDev(): bool
    {
        if (app()->isDev() || $this->force) {
            return true;
        }

        /* @var bool */
        return new Prompt(style('Running seeder in production?')->textRed(), [
            'yes' => fn () => true,
            'no'  => fn () => false,
        ], 'no')
            ->selection([
                style('yes')->textDim(),
                ' no',
            ])
            ->option();
    }

    /**
     * {@inheritdoc}
     *
     * @return int Exit code: always 0.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function main(): int
    {
        $class     = $this->class;
        $namespace = $this->option('name-space');
        $exit      = 0;

        if (false === $this->runInDev()) {
            return 2;
        }

        if (null !== $class && null !== $namespace) {
            warn('Use only one class or namespace, be specific.')->out(false);

            return 1;
        }

        if (null === $class && null !== $namespace) {
            $class = $namespace;
        }

        if ($class !== null && null === $namespace) {
            $class = 'Database\\Seeders\\' . $class;
        }

        if (null === $class && null === $namespace) {
            $class = 'Database\\Seeders\\DatabaseSeeder';
        }

        if (false === class_exists($class)) {
            warn("Class '{$class}::class' doest exist.")->out(false);

            return 1;
        }

        info('Running seeders...')->out(false);
        try {
            app()->call([$class, 'run']);

            success('Success run seeder ' . $class)->out(false);
        } catch (Throwable $th) {
            warn($th->getMessage())->out(false);
            $exit = 1;
        }

        return $exit;
    }

    /**
     * Generates a new database seeder class file.
     *
     * The seeder is created using the default application namespace and
     * boilerplate structure, unless the target file already exists and
     * execution is not forced.
     *
     * @return int Returns 0 on successful seeder creation, or 1 if the operation fails.
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function make(): int
    {
        $class = $this->option[0] ?? null;

        if (null === $class) {
            warn('command make:seed require class name')->out(false);

            return 1;
        }

        if (file_exists(get_path('path.seeder', $class . '.php')) && !$this->force) {
            warn("Class '{$class}::class' already exist.")->out(false);

            return 1;
        }

        $make = new Generate($class);
        $make->tabIndent(' ');
        $make->tabSize(4);
        $make->namespace('Database\Seeders');
        $make->use('Omega\Database\Seeder\AbstractSeeder');
        $make->extend('AbstractSeeder');
        $make->setEndWithNewLine();
        /** @noinspection PhpRedundantOptionalArgumentInspection */
        $make->addMethod('run')
            ->visibility(Method::PUBLIC_)
            ->setReturnType('void')
            ->body('// run some insert db');

        if (file_put_contents(get_path('path.seeder', $class . '.php'), $make->__toString())) {
            success('Success create seeder')->out();

            return 0;
        }

        error('Fail to create seeder')->out();

        return 1;
    }
}
