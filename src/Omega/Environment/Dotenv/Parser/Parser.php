<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Parser;

use Omega\Environment\Dotenv\Exceptions\InvalidFileException;
use Omega\Environment\Dotenv\ResultType\AbstractResult;
use Omega\Environment\Dotenv\ResultType\Success;
use Omega\Environment\Dotenv\Util\Regex;

use function array_merge;
use function array_reduce;
use function sprintf;

class Parser implements ParserInterface
{
    /**
     * Parse content into an entry array.
     *
     * @param string $content
     * @return Entry[]
     * @throws InvalidFileException
     */
    public function parse(string $content): array
    {
        return Regex::split("/(\r\n|\n|\r)/", $content)->mapError(static function () {
            return 'Could not split into separate lines.';
        })->flatMap(static function (array $lines) {
            return self::process(Lines::process($lines));
        })->mapError(static function (string $error) {
            throw new InvalidFileException(sprintf('Failed to parse dotenv file. %s', $error));
        })->success()->get();
    }

    /**
     * Convert the raw entries into proper entries.
     *
     * @param string[] $entries
     * @return AbstractResult<Entry[], string>
     */
    private static function process(array $entries): AbstractResult
    {
        /** @var AbstractResult<Entry[], string> */
        return array_reduce($entries, static function (AbstractResult $result, string $raw) {
            return $result->flatMap(static function (array $entries) use ($raw) {
                return EntryParser::parse($raw)->map(static function (Entry $entry) use ($entries) {
                    /** @var Entry[] */
                    return array_merge($entries, [$entry]);
                });
            });
        }, Success::create([]));
    }
}
