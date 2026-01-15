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

use Omega\Application\Application;
use Omega\Console\AbstractCommand;
use Omega\Console\Style\Style;
use Omega\Support\PackageManifest;
use Throwable;

use function array_keys;
use function Omega\Console\error;
use function Omega\Console\info;

/**
 * PackageDiscoveryCommand
 *
 * This command handles the discovery of composer packages and generates a cached package manifest.
 * It provides a console interface to trigger the discovery process and outputs the progress
 * in a formatted style for better readability. Any errors during the process are caught and displayed.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class PackageDiscoveryCommand extends AbstractCommand
{
    /**
     * Command registration configuration.
     *
     * Defines the pattern used to invoke the command and the method to execute.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $command = [
        [
            'pattern' => 'package:discovery',
            'fn'      => [self::class, 'discovery'],
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
                'package:discovery' => 'Discovery package in composer',
            ],
            'options'   => [],
            'relation'  => [],
        ];
    }

    /**
     * Discover and cache composer packages.
     *
     * This method retrieves the PackageManifest service from the application container,
     * builds the package cache, and displays the progress for each package in a formatted style.
     * If the cache creation fails, the exception message is printed and a non-zero status is returned.
     *
     * @param Application $app The application instance providing access to the container and services
     * @return int Returns 0 on success, 1 if an error occurs during package cache creation
     */
    public function discovery(Application $app): int
    {
        $package = $app[PackageManifest::class];
        info('Trying build package cache.')->out(false);
        try {
            $package->build();

            $packages = (fn () => $this->{'getPackageManifest'}())->call($package) ?? [];
            $style    = new Style();
            foreach (array_keys($packages) as $name) {
                $length = $this->getWidth(40, 60) - strlen($name) - 4;
                $style->push($name)->repeat('.', $length)->textDim()->push('DONE')->textGreen()->newLines();
            }
            $style->out(false);
        } catch (Throwable $th) {
            error($th->getMessage())->out(false);
            error('Can\'t create package manifest cache file.')->out();

            return 1;
        }

        return 0;
    }
}
