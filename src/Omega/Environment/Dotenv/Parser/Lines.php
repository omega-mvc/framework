<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Parser;

use Omega\Environment\Dotenv\Util\Regex;
use Omega\Environment\Dotenv\Util\Str;

use function implode;
use function str_replace;
use function trim;

class Lines
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
     * Process the array of lines of environment variables.
     *
     * This will produce an array of raw entries, one per variable.
     *
     * @param string[] $lines
     * @return string[]
     */
    public static function process(array $lines): array
    {
        $output = [];
        $multiline = false;
        $multilineBuffer = [];

        foreach ($lines as $line) {
            [$multiline, $line, $multilineBuffer] = self::multilineProcess($multiline, $line, $multilineBuffer);

            if (!$multiline && !self::isCommentOrWhitespace($line)) {
                $output[] = $line;
            }
        }

        return $output;
    }

    /**
     * Used to make all multiline variable process.
     *
     * @param bool     $multiline
     * @param string   $line
     * @param string[] $buffer
     * @return array{bool,string, string[]}
     */
    private static function multilineProcess(bool $multiline, string $line, array $buffer): array
    {
        $startsOnCurrentLine = !$multiline && self::looksLikeMultilineStart($line);

        // check if $line can be multiline variable
        if ($startsOnCurrentLine) {
            $multiline = true;
        }

        if ($multiline) {
            $buffer[] = $line;

            if (self::looksLikeMultilineStop($line, $startsOnCurrentLine)) {
                $multiline = false;
                $line = implode("\n", $buffer);
                $buffer = [];
            }
        }

        return [$multiline, $line, $buffer];
    }

    /**
     * Determine if the given line can be the start of a multiline variable.
     *
     * @param string $line
     * @return bool
     */
    private static function looksLikeMultilineStart(string $line): bool
    {
        return Str::pos($line, '="')->map(static function () use ($line) {
            return self::looksLikeMultilineStop($line, true) === false;
        })->getOrElse(false);
    }

    /**
     * Determine if the given line can be the start of a multiline variable.
     *
     * @param string $line
     * @param bool   $started
     * @return bool
     */
    private static function looksLikeMultilineStop(string $line, bool $started): bool
    {
        if ($line === '"') {
            return true;
        }

        return Regex::occurrences(
            '/(?=([^\\\\]"))/',
            str_replace('\\\\', '', $line)
        )->map(static function (int $count) use ($started) {
                return $started ? $count > 1 : $count >= 1;
        })->success()->getOrElse(false);
    }

    /**
     * Determine if the line in the file is a comment or whitespace.
     *
     * @param string $line
     * @return bool
     */
    private static function isCommentOrWhitespace(string $line): bool
    {
        $line = trim($line);

        return $line === '' || (isset($line[0]) && $line[0] === '#');
    }
}
