<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Console\Traits\InteractsWithConsoleOutputTrait;
use Omega\Application\ApplicationManifest;
use Throwable;

#[AsCommand(
    name: 'package:discover',
    description: 'Discover and cache composer packages manifest'
)]
final class PackageDiscoverCommand extends AbstractCommand
{
    use InteractsWithConsoleOutputTrait;

    /**
     * @return int Exit code
     */
    public function __invoke(): int
    {
        $this->io->info('Discovery packages in composer...');

        /** @var ApplicationManifest $applicationManifest */
        $applicationManifest = $this->app[ApplicationManifest::class];

        try {
            $applicationManifest->build();

            /** @var array $packages */
            $packages = (fn () => $this->{'getApplicationManifest'}())->call($applicationManifest) ?? [];

            if (empty($packages)) {
                $this->io->warning('No discoverable packages found.');
                return self::SUCCESS;
            }

            foreach (array_keys($packages) as $name) {
                $left  = '<fg=white>' . $name . '</>';
                $right = '<fg=green>DONE</>';

                $this->componentsTwoColumns($left, $right, 2, 50);
            }

            $this->io->newLine();
            $this->io->success('Package manifest generated successfully.');
        } catch (Throwable $th) {
            $this->io->error($th->getMessage());
            $this->io->error("Can't create package manifest cache file.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
