<?php

/**
 * Genera un albero delle cartelle e dei file di 'php-vc',
 * includendo solo 'vendor/omegamvc' dentro 'vendor',
 * e scrive il risultato su 'albero.txt'.
 */

function creaAlbero(string $directory, int $livello = 0): string
{
    $albero = '';
    $elementi = scandir($directory);

    foreach ($elementi as $elemento) {
        if ($elemento === '.' || $elemento === '..') {
            continue;
        }

        $path = $directory . DIRECTORY_SEPARATOR . $elemento;

        // Gestione speciale per 'vendor'
        if (basename($directory) === 'vendor') {
            if ($elemento !== 'omegamvc') {
                continue;
            }
        }

        $indent = str_repeat('  ', $livello);
        $albero .= $indent . '- ' . $elemento . PHP_EOL;

        if (is_dir($path)) {
            $albero .= creaAlbero($path, $livello + 1);
        }
    }

    return $albero;
}

// Punto di partenza
$directoryBase = __DIR__ . '/fixtures/';

// Genera albero e salva su file
$albero = creaAlbero($directoryBase);
file_put_contents('albero.txt', $albero);

echo "Albero generato in 'albero.txt'" . PHP_EOL;
