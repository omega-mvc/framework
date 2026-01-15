<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Util;

use Omega\Environment\Dotenv\Option\AbstractOption;
use Omega\Environment\Dotenv\ResultType\AbstractResult;
use Omega\Environment\Dotenv\ResultType\Error;
use Omega\Environment\Dotenv\ResultType\Success;

use function in_array;
use function mb_convert_encoding;
use function mb_list_encodings;
use function mb_strlen;
use function mb_strpos;
use function mb_substr;
use function sprintf;
use function substr;

/**
 * @internal
 */
class Str
{
    /**
     * This class is a singleton.
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Convert a string to UTF-8 from the given encoding.
     *
     * @param string      $input
     * @param string|null $encoding
     *
     * @return AbstractResult<string, string>
     */
    public static function utf8(string $input, ?string $encoding = null): AbstractResult
    {
        if ($encoding !== null && !in_array($encoding, mb_list_encodings(), true)) {
            /** @var AbstractResult<string, string> */
            return Error::create(
                sprintf('Illegal character encoding [%s] specified.', $encoding)
            );
        }

        $converted = $encoding === null ?
            @mb_convert_encoding($input, 'UTF-8') :
            @mb_convert_encoding($input, 'UTF-8', $encoding);

        if (!is_string($converted)) {
            /** @varAbstractResult<string, string> */
            return Error::create(
                sprintf('Conversion from encoding [%s] failed.', $encoding ?? 'NULL')
            );
        }

        if (str_starts_with($converted, "\xEF\xBB\xBF")) {
            $converted = substr($converted, 3);
        }

        /** @var AbstractResult<string, string> */
        return Success::create($converted);
    }

    /**
     * Search for a given substring of the input.
     *
     * @param string $haystack
     * @param string $needle
     *
     * @return AbstractOption<int>
     */
    public static function pos(string $haystack, string $needle): AbstractOption
    {
        /** @var AbstractOption<int> */
        return AbstractOption::fromValue(mb_strpos($haystack, $needle, 0, 'UTF-8'), false);
    }

    /**
     * Grab the specified substring of the input.
     *
     * @param string   $input
     * @param int      $start
     * @param int|null $length
     *
     * @return string
     */
    public static function substr(string $input, int $start, ?int $length = null): string
    {
        return mb_substr($input, $start, $length, 'UTF-8');
    }

    /**
     * Compute the length of the given string.
     *
     * @param string $input
     *
     * @return int
     */
    public static function len(string $input): int
    {
        return mb_strlen($input, 'UTF-8');
    }
}
