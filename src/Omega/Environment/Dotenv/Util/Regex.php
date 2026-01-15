<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Util;

use Omega\Environment\Dotenv\ResultType\AbstractResult;
use Omega\Environment\Dotenv\ResultType\Error;
use Omega\Environment\Dotenv\ResultType\Success;

use function preg_last_error;
use function preg_last_error_msg;
use function preg_match;
use function preg_match_all;
use function preg_replace_callback;
use function preg_split;

use const PREG_NO_ERROR;

class Regex
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
     * Perform a preg match, wrapping up the result.
     *
     * @param string $pattern
     * @param string $subject
     * @return AbstractResult<bool, string>
     */
    public static function matches(string $pattern, string $subject): AbstractResult
    {
        return self::pregAndWrap(static function (string $subject) use ($pattern) {
            return @preg_match($pattern, $subject) === 1;
        }, $subject);
    }

    /**
     * Perform a preg match all, wrapping up the result.
     *
     * @param string $pattern
     * @param string $subject
     * @return AbstractResult<int, string>
     */
    public static function occurrences(string $pattern, string $subject): AbstractResult
    {
        return self::pregAndWrap(static function (string $subject) use ($pattern) {
            return (int) @preg_match_all($pattern, $subject);
        }, $subject);
    }

    /**
     * Perform a preg replace callback, wrapping up the result.
     *
     * @param string                     $pattern
     * @param callable(string[]): string $callback
     * @param string                     $subject
     * @param int|null                   $limit
     * @return AbstractResult<string, string>
     */
    public static function replaceCallback(
        string $pattern,
        callable $callback,
        string $subject,
        ?int $limit = null
    ): AbstractResult {
        return self::pregAndWrap(static function (string $subject) use ($pattern, $callback, $limit) {
            return (string) @preg_replace_callback($pattern, $callback, $subject, $limit ?? -1);
        }, $subject);
    }

    /**
     * Perform a preg split, wrapping up the result.
     *
     * @param string $pattern
     * @param string $subject
     * @return AbstractResult<string[], string>
     */
    public static function split(string $pattern, string $subject): AbstractResult
    {
        return self::pregAndWrap(static function (string $subject) use ($pattern) {
            /** @var string[] */
            return (array) @preg_split($pattern, $subject);
        }, $subject);
    }

    /**
     * Perform a preg operation, wrapping up the result.
     *
     * @template V
     *
     * @param callable(string): V $operation
     * @param string              $subject
     * @return AbstractResult<V, string>
     */
    private static function pregAndWrap(callable $operation, string $subject): AbstractResult
    {
        $result = $operation($subject);

        if (preg_last_error() !== PREG_NO_ERROR) {
            /** @var AbstractResult<V,string> */
            return Error::create(preg_last_error_msg());
        }

        /** @var AbstractResult<V,string> */
        return Success::create($result);
    }
}
