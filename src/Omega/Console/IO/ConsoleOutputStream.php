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

namespace Omega\Console\IO;

use function defined;
use function fopen;
use function function_exists;
use function getenv;
use function implode;
use function stripos;

use const PHP_OS;
use const STDERR;

/**
 * Class ConsoleOutputStream
 *
 * A specialized OutputStream that writes to the console error output.
 * Detects interactive capabilities and adjusts behavior for environments
 * like IBM iSeries (OS400).
 *
 * @category   Omega
 * @package    Console
 * @subpackage IO
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class ConsoleOutputStream extends OutputStream implements OutputStreamInterface
{
    /**
     * Constructor.
     *
     * Opens the appropriate error stream (STDERR if available, otherwise STDOUT)
     * and initializes the parent OutputStream with it.
     */
    public function __construct()
    {
        parent::__construct($this->openErrorStream());
    }

    /**
     * {@inheritdoc}
     */
    public function isInteractive(): bool
    {
        return false === $this->isRunningOS400();
    }

    /**
     * Opens the appropriate stream to write error output.
     *
     * @return resource The writable stream resource
     */
    private function openErrorStream()
    {
        if (false === $this->isInteractive()) {
            return fopen('php://output', 'w');
        }

        return defined('STDERR') ? STDERR : (@fopen('php://stderr', 'w') ?: fopen('php://output', 'w'));
    }

    /**
     * Determines if the current environment is IBM iSeries (OS400),
     * which requires special handling due to ASCII/EBCDIC encoding issues.
     *
     * @return bool True if running on OS400, false otherwise
     */
    private function isRunningOS400(): bool
    {
        $checks = [
            function_exists('php_uname') ? php_uname('s') : '',
            getenv('OSTYPE'),
            PHP_OS,
        ];

        return false !== stripos(implode(';', $checks), 'OS400');
    }
}
