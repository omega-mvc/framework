<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Parser;

use Omega\Environment\Dotenv\Exceptions\InvalidFileException;

interface ParserInterface
{
    /**
     * Parse content into an entry array.
     *
     * @param string $content
     * @return Entry[]
     * @throws InvalidFileException
     */
    public function parse(string $content): array;
}
