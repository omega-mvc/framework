<?php

/**
 * Part of Omega MVC - Support Package
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Support\Enum;

use Omega\Support\Enum\Exceptions\BadInstantiationException;
use Omega\Support\Enum\Exceptions\InvalidValueException;
use ReflectionClass;

use function array_flip;
use function array_keys;
use function array_values;
use function constant;
use function in_array;

/**
 * Base class for creating enumerations.
 *
 * This class provides a framework for defining strongly-typed enumerations in PHP,
 * ensuring that only predefined values can be used. It prevents instantiation with
 * invalid values and allows conversion between string representations and constants.
 *
 * @category   Omega
 * @package    Support
 * @subpackage Enum
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
abstract class AbstractEnum
{
    /** @var string The selected enum value. */
    protected string $value;

    /** @var string[] Custom string representations for enum values.
     *                This array allows mapping constant values to human-readable strings. */
    protected array $strings = [];

    /**
     * Constructs a new enum instance.
     *
     * Ensures that only valid values can be used. The value is validated against
     * the constants defined in the concrete enum class.
     *
     * @param mixed $value The value to assign to this enum.
     * @throws InvalidValueException If the value is not a valid enum value.
     */
    final protected function __construct(mixed $value)
    {
        $this->value = self::constantFrom($value);
    }

    /**
     * Creates an instance of the enum from a given value.
     *
     * @param mixed $value The value to assign.
     * @return static The created enum instance.
     * @throws BadInstantiationException If called on AbstractEnum.
     * @throws InvalidValueException If the value is not a valid enum value.
     */
    final public static function from(mixed $value): static
    {
        if (static::class === self::class) {
            throw new BadInstantiationException(
                'Cannot instantiate an abstract class directly: use a concrete implementation instead.'
            );
        }

        return new static($value);
    }

    /**
     * Retrieves the constant name corresponding to a given enum value.
     *
     * @param mixed $value The enum value.
     * @return string The constant name associated with the value.
     * @throws InvalidValueException If the value does not match any defined constant.
     */
    final public static function constantFrom(mixed $value): string
    {
        if (!in_array($value, static::values(), true)) {
            throw new InvalidValueException(
                "Invalid value provided for enum '%s'. Allowed values: [%s]" . static::class
            );
        }

        return (string)array_flip(new ReflectionClass(static::class)->getConstants())[$value];
    }

    /**
     * Returns a list of all possible values for the enum.
     *
     * @return array List of valid enum values.
     */
    final public static function values(): array
    {
        return array_values(new ReflectionClass(static::class)->getConstants());
    }

    /**
     * Returns a list of all constant names for the enum.
     *
     * @return array List of enum constant names.
     */
    final public static function enum(): array
    {
        return array_keys(new ReflectionClass(static::class)->getConstants());
    }

    /**
     * Gets the actual value of the enum instance.
     *
     * @return mixed The stored enum value.
     */
    final public function value(): mixed
    {
        return constant("static::" . $this->value);
    }

    /**
     * Converts the enum instance to a string.
     *
     * If a custom string mapping exists for the enum value, it returns that;
     * otherwise, it returns the raw value.
     *
     * @return string The string representation of the enum value.
     */
    final public function __toString(): string
    {
        return array_key_exists($this->value(), $this->strings)
            ? $this->strings[$this->value()]
            : $this->value;
    }
}
