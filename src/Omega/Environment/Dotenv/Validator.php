<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv;

use Omega\Environment\Dotenv\Exceptions\ValidationException;
use Omega\Environment\Dotenv\Repository\RepositoryInterface;
use Omega\Environment\Dotenv\Util\Regex;
use Omega\Environment\Dotenv\Util\Str;

use function count;
use function ctype_digit;
use function filter_var;
use function implode;
use function in_array;
use function sprintf;
use function trim;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;

readonly class Validator
{
    /**
     * Create a new validator instance.
     *
     * @param RepositoryInterface $repository
     * @param string[]            $variables
     * @return void
     */
    public function __construct(
        private RepositoryInterface $repository,
        private array $variables
    ) {
    }

    /**
     * Assert that each variable is present.
     *
     * @return Validator
     * @throws ValidationException
     */
    public function required(): Validator
    {
        return $this->assert(
            static function (?string $value) {
                return $value !== null;
            },
            'is missing'
        );
    }

    /**
     * Assert that each variable is not empty.
     *
     * @return Validator
     * @throws ValidationException
     */
    public function notEmpty(): Validator
    {
        return $this->assertNullable(
            static function (string $value) {
                return Str::len(trim($value)) > 0;
            },
            'is empty'
        );
    }

    /**
     * Assert that each specified variable is an integer.
     *
     * @return Validator
     * @throws ValidationException
     */
    public function isInteger(): Validator
    {
        return $this->assertNullable(
            static function (string $value) {
                return ctype_digit($value);
            },
            'is not an integer'
        );
    }

    /**
     * Assert that each specified variable is a boolean.
     *
     * @return Validator
     * @throws ValidationException
     */
    public function isBoolean(): Validator
    {
        return $this->assertNullable(
            static function (string $value) {
                if ($value === '') {
                    return false;
                }

                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null;
            },
            'is not a boolean'
        );
    }

    /**
     * Assert that each variable is amongst the given choices.
     *
     * @param string[] $choices
     * @return Validator
     * @throws ValidationException
     */
    public function allowedValues(array $choices): Validator
    {
        return $this->assertNullable(
            static function (string $value) use ($choices) {
                return in_array($value, $choices, true);
            },
            sprintf('is not one of [%s]', implode(', ', $choices))
        );
    }

    /**
     * Assert that each variable matches the given regular expression.
     *
     * @param string $regex
     * @return Validator
     * @throws ValidationException
     */
    public function allowedRegexValues(string $regex): Validator
    {
        return $this->assertNullable(
            static function (string $value) use ($regex) {
                return Regex::matches($regex, $value)->success()->getOrElse(false);
            },
            sprintf('does not match "%s"', $regex)
        );
    }

    /**
     * Assert that the callback returns true for each variable.
     *
     * @param callable(?string):bool $callback
     * @param string                 $message
     * @return Validator
     * @throws ValidationException
     */
    public function assert(callable $callback, string $message): Validator
    {
        $failing = [];

        foreach ($this->variables as $variable) {
            if ($callback($this->repository->get($variable)) === false) {
                $failing[] = sprintf('%s %s', $variable, $message);
            }
        }

        if (count($failing) > 0) {
            throw new ValidationException(sprintf(
                'One or more environment variables failed assertions: %s.',
                implode(', ', $failing)
            ));
        }

        return $this;
    }

    /**
     * Assert that the callback returns true for each variable.
     *
     * Skip checking null variable values.
     *
     * @param callable(string):bool $callback
     * @param string                $message
     * @return Validator
     * @throws ValidationException
     */
    public function assertNullable(callable $callback, string $message): Validator
    {
        return $this->assert(
            static function (?string $value) use ($callback) {
                if ($value === null) {
                    return true;
                }

                return $callback($value);
            },
            $message
        );
    }
}
