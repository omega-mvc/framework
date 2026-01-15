<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Repository\Adapter;

use Omega\Environment\Dotenv\Option\AbstractOption;
use Omega\Environment\Dotenv\Option\None;
use Omega\Environment\Dotenv\Option\Some;

use function function_exists;
use function getenv;
use function is_string;
use function putenv;

class PutenvAdapter implements AdapterInterface
{
    /**
     * Create a new putenv adapter instance.
     *
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * Create a new instance of the adapter, if it is available.
     *
     * @return AbstractOption<AdapterInterface>
     */
    public static function create(): AbstractOption
    {
        if (self::isSupported()) {
            /** @var AbstractOption<AdapterInterface> */
            return Some::create(new self());
        }

        return None::create();
    }

    /**
     * Determines if the adapter is supported.
     *
     * @return bool
     */
    private static function isSupported(): bool
    {
        return function_exists('getenv') && function_exists('putenv');
    }

    /**
     * Read an environment variable, if it exists.
     *
     * @param non-empty-string $name
     * @return AbstractOption<string>
     */
    public function read(string $name): AbstractOption
    {
        /** @var AbstractOption<string> */
        return AbstractOption::fromValue(getenv($name), false)->filter(static function ($value) {
            return is_string($value);
        });
    }

    /**
     * Write to an environment variable, if possible.
     *
     * @param non-empty-string $name
     * @param string           $value
     * @return bool
     */
    public function write(string $name, string $value): bool
    {
        putenv("$name=$value");

        return true;
    }

    /**
     * Delete an environment variable, if possible.
     *
     * @param non-empty-string $name
     * @return bool
     */
    public function delete(string $name): bool
    {
        putenv($name);

        return true;
    }
}
