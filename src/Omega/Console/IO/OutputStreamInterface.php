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

use Omega\Console\Exceptions\InvalidStreamException;

/**
 * OutputStreamInterface
 *
 * This interface defines the contract for output streams used in console applications.
 * It abstracts writing to a stream (like STDOUT, file, or a custom output buffer)
 * and allows checking whether the stream is interactive (e.g., connected to a terminal).
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
interface OutputStreamInterface
{
    /**
     * Write a string buffer to the output stream.
     *
     * Implementations should handle errors appropriately and throw
     * an InvalidStreamException if the write operation fails.
     *
     * @param string $buffer The string data to write to the stream
     * @return void
     * @throws InvalidStreamException If writing to the stream fails
     */
    public function write(string $buffer): void;

    /**
     * Determine if the output stream is interactive.
     *
     * An interactive stream is typically connected to a terminal
     * and can handle features like colors, cursor positioning,
     * or user input detection.
     *
     * @return bool True if the stream is interactive, false otherwise
     */
    public function isInteractive(): bool;
}
