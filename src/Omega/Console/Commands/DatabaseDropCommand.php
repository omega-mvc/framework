<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'database:drop',
    description: 'Drop the specified database'
)]
final class DatabaseDropCommand extends AbstractMigrationCommand
{
    /**
     * Configurazione del comando.
     */
    protected function configure(): void
    {
        // Usiamo il concatenamento fluido ereditato dalla base
        $this->addDatabaseOption()
            ->addForceOption()
            ->addYesOption();
    }

    /**
     * Logica principale del comando.
     */
    /**
     * @param bool $silent
     * @return int
     * @throws BindingResolutionException
     * @throws CircularAliasException
     * @throws EntryNotFoundException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     */
    public function handle(bool $silent = false): int
    {
        return $this->databaseDrop();
    }
}
