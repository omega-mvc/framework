<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Store\File;

use function rtrim;

use const DIRECTORY_SEPARATOR;

class Paths
{
    /**
     * This class is a singleton.
     *
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * Returns the full paths to the files.
     *
     * @param string[] $paths
     * @param string[] $names
     * @return string[]
     */
    public static function filePaths(array $paths, array $names): array
    {
        $files = [];

        foreach ($paths as $path) {
            foreach ($names as $name) {
                $files[] = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
            }
        }

        return $files;
    }
}
