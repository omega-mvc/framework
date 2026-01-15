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

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Style\ProgressBar;
use Omega\Console\Traits\PrintHelpTrait;
use Omega\Container\Provider\AbstractServiceProvider;

use function count;
use function is_dir;
use function Omega\Console\success;

/**
 * ServeCommand
 *
 * Provides a development HTTP server for the application using PHP's built-in server.
 * This command allows running the application locally on a specified port and,
 * optionally, exposing it to the local network.
 *
 * It is intended for development and testing purposes only and should not be
 * used in production environments.
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
 * @property bool   $force Whether to force the import, overwriting existing files.
 * @property string $tag   The tag to identify specific commands to run.
 */
class VendorImportCommand extends AbstractCommand
{
    use PrintHelpTrait;

    /**
     * Progress bar instance used to track the progress of vendor imports.
     *
     * It reflects the current position, total workload, and visual feedback
     * during file or directory copy operations.
     */
    private ProgressBar $status;

    /**
     * Command registration configuration.
     *
     * Defines the pattern used to invoke the command and the method to execute.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $command = [
        [
            'pattern' => 'vendor:import',
            'fn'      => [self::class, 'main'],
            'default' => [
                'tag'   => '*',
                'force' => false,
            ],
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
                'vendor:import' => 'Import package in vendor.',
            ],
            'options'   => [
                '--tag' => 'Specify the tag to run specific commands.',
            ],
            'relation'  => [
                'vendor:import' => ['--tag', '--force'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return int Exit code: always 0.
     */
    public function main(): int
    {
        $this->status = new ProgressBar();
        $this->importItem(AbstractServiceProvider::getModules());

        return 0;
    }

    /**
     * Import vendor modules defined by service providers.
     *
     * Iterates through the given modules, filtering them by tag and importing
     * files or directories into the target vendor paths while updating
     * the progress bar accordingly.
     *
     * @param array<string, array<string, string>> $modules
     *        A map of tags to source/target path pairs to be imported.
     * @return void
     */
    protected function importItem(array $modules): void
    {
        $this->status->mask = count($modules);
        $current            = 0;
        $added              = 0;

        foreach ($modules as $tag => $module) {
            $current++;

            if ($tag === $this->tag || $this->tag === '*') {
                foreach ($module as $from => $to) {
                    $added++;
                    if (is_dir($from)) {
                        $status = AbstractServiceProvider::importDir($from, $to, $this->force);
                        $this->status($current, $status, $from, $to);

                        continue 2;
                    }

                    $status = AbstractServiceProvider::importFile($from, $to, $this->force);
                    $this->status($current, $status, $from, $to);
                }
            }
        }

        if ($current > 0) {
            success('Done ')->push((string)$added)->textYellow()->push(' file/folder has been added.')->out(false);
        }
    }

    /**
     * Update the progress bar with the current import status.
     *
     * Advances the progress indicator and displays contextual information
     * about the file or directory being copied, only when the operation
     * succeeds.
     *
     * @param int    $current Current progress position
     * @param bool   $success Whether the import operation succeeded
     * @param string $from    Source path of the imported file or directory
     * @param string $to      Destination path of the imported file or directory
     * @return void
     */
    protected function status(int $current, bool $success, string $from, string $to): void
    {
        if (false === $success) {
            return;
        }

        $this->status->current = $current;
        $this->status->tickWith(':progress :percent :status', [
            'status' => fn (int $current, int $max): string => "Copying file/directory from '$from' to '$to'.",
        ]);
    }
}
