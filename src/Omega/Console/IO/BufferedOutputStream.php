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

/**
 * Class BufferedOutputStream
 *
 * An output stream that accumulates written data in memory.
 * Data can be retrieved and cleared with fetch().
 * Typically used for testing or capturing output without writing to a real stream.
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
class BufferedOutputStream implements OutputStreamInterface
{
    /**
     * Constructor.
     *
     * Initializes an empty buffer.
     */
    private string $buffer = '';

    /**
     * Empties the buffer and returns its contents.
     *
     * @return string The buffered output
     */
    public function fetch(): string
    {
        $content      = $this->buffer;
        $this->buffer = '';

        return $content;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $buffer): void
    {
        $this->buffer .= $buffer;
    }

    /**
     * {@inheritdoc}
     */
    public function isInteractive(): bool
    {
        return false;
    }
}
