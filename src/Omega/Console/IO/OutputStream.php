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

use function fwrite;
use function get_resource_type;
use function is_resource;
use function str_contains;
use function stream_get_meta_data;
use function stream_isatty;

use const STDOUT;

/**
 * OutputStream
 *
 * This class implements the OutputStreamInterface and provides a concrete
 * implementation for writing to a stream resource in console applications.
 * It ensures the stream is valid and writable, and provides a method to check
 * whether the stream is interactive (i.e., connected to a terminal).
 *
 * Typical usage includes writing output to STDOUT, a file, or a custom stream.
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
class OutputStream implements OutputStreamInterface
{
    /** @var resource The stream must be writable (e.g., STDOUT or a writable file handle) */
    private mixed $stream;

    /**
     * Constructor.
     *
     * Initializes the OutputStream with a given resource and validates that
     * it is a writable stream.
     *
     * @param resource|false|null $stream The stream resource to write to. Defaults to STDOUT.
     * @throws InvalidStreamException If the stream is not a valid resource or not writable
     */
    public function __construct(mixed $stream = STDOUT)
    {
        if (!is_resource($stream) || get_resource_type($stream) !== 'stream') {
            throw new InvalidStreamException('Expected a valid stream');
        }

        $meta = stream_get_meta_data($stream);
        if (str_contains($meta['mode'], 'r') && !str_contains($meta['mode'], '+')) {
            throw new InvalidStreamException('Expected a writable stream');
        }

        $this->stream = $stream;
    }

    /**
     * {@inheritdoc}
     */
    public function write(string $buffer): void
    {
        if (fwrite($this->stream, $buffer) === false) {
            throw new InvalidStreamException('Failed to write to stream');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isInteractive(): bool
    {
        return stream_isatty($this->stream);
    }
}
