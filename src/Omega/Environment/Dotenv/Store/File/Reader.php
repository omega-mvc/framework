<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Store\File;

use Omega\Environment\Dotenv\Exceptions\InvalidEncodingException;
use Omega\Environment\Dotenv\Option\AbstractOption;
use Omega\Environment\Dotenv\Util\Str;

use function file_get_contents;

class Reader
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
     * Read the file(s), and return their raw content.
     *
     * We provide the file path as the key, and its content as the value. If
     * short circuit mode is enabled, then the returned array with have length
     * at most one. File paths that couldn't be read are omitted entirely.
     *
     * @param string[]    $filePaths
     * @param bool        $shortCircuit
     * @param string|null $fileEncoding
     * @return array<string, string>
     * @throws InvalidEncodingException
     *
     */
    public static function read(array $filePaths, bool $shortCircuit = true, ?string $fileEncoding = null): array
    {
        $output = [];

        foreach ($filePaths as $filePath) {
            $content = self::readFromFile($filePath, $fileEncoding);
            if ($content->isDefined()) {
                $output[$filePath] = $content->get();
                if ($shortCircuit) {
                    break;
                }
            }
        }

        return $output;
    }

    /**
     * Read the given file.
     *
     * @param string      $path
     * @param string|null $encoding
     * @return AbstractOption<string>
     * @throws InvalidEncodingException
     *
     */
    private static function readFromFile(string $path, ?string $encoding = null): AbstractOption
    {
        /** @var AbstractOption<string> $content */
        $content = AbstractOption::fromValue(@file_get_contents($path), false);

        return $content->flatMap(static function (string $content) use ($encoding) {
            return Str::utf8($content, $encoding)->mapError(static function (string $error) {
                throw new InvalidEncodingException($error);
            })->success();
        });
    }
}
