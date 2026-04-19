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

namespace Omega\Console\Traits;

use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Terminal;

trait InteractsWithConsoleOutputTrait
{
    private ?int $terminalWidth = null;

    protected function getTerminalWidth(): int
    {
        return $this->terminalWidth ??= new Terminal()->getWidth();
    }

    /**
     * Calcola la larghezza visibile di una singola stringa (senza tag ANSI)
     */
    protected function getVisibleWidth(string $string): int
    {
        return Helper::width(Helper::removeDecoration($this->output->getFormatter(), $string));
    }

    /**
     * Calcola la larghezza massima visibile tra un array di stringhe
     */
    protected function getVisibleMaxWidth(array $items): int
    {
        if (empty($items)) {
            return 0;
        }

        return array_reduce($items, function ($max, $item) {
            return max($max, $this->getVisibleWidth((string) $item));
        }, 0);
    }

    /**
     * Stampa un messaggio allineato a destra con un margine opzionale
     */
    protected function writeRight(string $message, int $margin = 2): void
    {
        $visualWidth = $this->getVisibleWidth($message);

        // USIAMO il metodo invece della proprietà diretta
        $width = $this->getTerminalWidth();

        $spacesCount = max(0, $width - $visualWidth - $margin);
        $this->output->writeln(str_repeat(' ', $spacesCount) . $message);
    }

    /**
     * Componente a due colonne separate da puntini (stile Laravel)
     */
    protected function componentsTwoColumns(string $left, string $right, int $leftMargin = 2, int $rightMargin = 2): void
    {
        $width = $this->getTerminalWidth();

        $leftVisible  = $this->getVisibleWidth($left);
        $rightVisible = $this->getVisibleWidth($right);

        // Ora il calcolo userà il valore reale (es. 120) e non 0
        $dotsCount = max(2, $width - $leftVisible - $rightVisible - $leftMargin - $rightMargin - 2);
        $dots = "<fg=gray>" . str_repeat('.', $dotsCount) . "</>";

        $this->output->writeln(sprintf(
            '%s%s %s %s%s',
            str_repeat(' ', $leftMargin),
            $left,
            $dots,
            $right,
            str_repeat(' ', $rightMargin)
        ));
    }
}
