<?php

declare(strict_types=1);

namespace Omega\Console\Commands;

use Symfony\Component\Finder\Finder;

use function is_dir;

trait ViewCommandFilesTrait
{
    /**
     * Cerca ricorsivamente file in una directory usando un pattern.
     *
     * @param string $directory Directory di partenza
     * @param string $pattern   Pattern tipo '*.php'
     * @return array<int,string> Array di percorsi assoluti dei file trovati
     */
    protected function findFiles(string $directory, string $pattern): array
    {
        if (!is_dir($directory)) {
            return [];
        }

        $finder = new Finder();
        $finder->files()
            ->in($directory)
            ->name($pattern);

        $files = [];
        foreach ($finder as $file) {
            $files[] = $file->getRealPath();
        }

        return $files;
    }
}
