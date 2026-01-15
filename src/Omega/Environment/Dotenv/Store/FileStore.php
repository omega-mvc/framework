<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Store;

use Omega\Environment\Dotenv\Exceptions\InvalidEncodingException;
use Omega\Environment\Dotenv\Exceptions\InvalidPathException;
use Omega\Environment\Dotenv\Store\File\Reader;

use function count;
use function implode;
use function sprintf;

readonly class FileStore implements StoreInterface
{
    /**
     * Create a new file store instance.
     *
     * @param string[]    $filePaths
     * @param bool        $shortCircuit
     * @param string|null $fileEncoding
     * @return void
     */
    public function __construct(
        private array $filePaths,
        private bool $shortCircuit,
        private ?string $fileEncoding = null
    ) {
    }

    /**
     * Read the content of the environment file(s).
     *
     * @return string
     * @throws InvalidEncodingException
     * @throws InvalidPathException
     *
     */
    public function read(): string
    {
        if ($this->filePaths === []) {
            throw new InvalidPathException(
                'At least one environment file path must be provided.'
            );
        }

        $contents = Reader::read($this->filePaths, $this->shortCircuit, $this->fileEncoding);

        if (count($contents) > 0) {
            return implode("\n", $contents);
        }

        throw new InvalidPathException(
            sprintf(
                'Unable to read any of the environment file(s) at [%s].',
                implode(', ', $this->filePaths)
            )
        );
    }
}
