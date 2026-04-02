<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Attribute\AsCommand;
use Omega\Support\PackageManifest;
use Throwable;

#[AsCommand(
    name: 'package:discover',
    description: 'Discover and cache composer packages manifest'
)]
final class PackageDiscoverCommand extends AbstractCommand
{
    /**
     * @return int Exit code
     */
    public function __invoke(): int
    {
        $this->io->info('Discovery packages in composer...');

        // 1. Recupero il servizio dal container (ereditato da AbstractCommand via $this->app)
        /** @var PackageManifest $packageManifest */
        $packageManifest = $this->app[PackageManifest::class];

        try {
            // 2. Costruzione del manifest
            $packageManifest->build();

            // 3. Recupero l'elenco dei pacchetti tramite lo scope della closure (logica originale)
            /** @var array $packages */
            $packages = (fn () => $this->{'getPackageManifest'}())->call($packageManifest) ?? [];

            if (empty($packages)) {
                $this->io->warning('No discoverable packages found.');
                return self::SUCCESS;
            }

            // 4. Output stilizzato
            foreach (array_keys($packages) as $name) {
                // Calcolo dello spazio per i puntini (scansionabilità)
                $dots = str_repeat('.', max(2, 50 - strlen($name)));

                // Usiamo il metodo line() per un controllo granulare o success()
                $this->io->text(sprintf(
                    ' <info>%s</info> <fg=gray>%s</> <fg=green>DONE</>',
                    $name,
                    $dots
                ));
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
