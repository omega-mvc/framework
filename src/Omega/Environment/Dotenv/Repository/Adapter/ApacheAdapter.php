<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Repository\Adapter;

use Omega\Environment\Dotenv\Option\AbstractOption;
use Omega\Environment\Dotenv\Option\None;
use Omega\Environment\Dotenv\Option\Some;

use function apache_getenv;
use function apache_setenv;
use function function_exists;
use function is_string;

class ApacheAdapter implements AdapterInterface
{
    /**
     * Create a new apache adapter instance.
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
     * This happens if PHP is running as an Apache module.
     *
     * @return bool
     */
    private static function isSupported(): bool
    {
        return function_exists('apache_getenv') && function_exists('apache_setenv');
    }

    /**
     * Read an environment variable, if it exists.
     *
     * @param non-empty-string $name
     *
     * @return AbstractOption<string>
     */
    public function read(string $name): AbstractOption
    {
        /** @var AbstractOption<string> */
        return AbstractOption::fromValue(apache_getenv($name))->filter(static function ($value) {
            return is_string($value) && $value !== '';
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
        return apache_setenv($name, $value);
    }

    /**
     * Delete an environment variable, if possible.
     *
     * @param non-empty-string $name
     * @return bool
     */
    public function delete(string $name): bool
    {
        return apache_setenv($name, '');
    }
}
