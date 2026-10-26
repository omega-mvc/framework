<?php

declare(strict_types=1);

namespace Omega\Validator\Traits;

/**
 * Trait contain invert exist validation method from parent.
 *
 * TODO: check every method with GUMP method
 */
trait InvertValidationTrait
{
    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_required(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_required($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     *
     * @return bool
     */
    protected function validate_invert_contains(string $field, array $input, array $params): bool
    {
        return !$this->validate_contains($field, $input, $params);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     *
     * @return bool
     */
    protected function validate_invert_contains_list(string $field, array $input, array $params): bool
    {
        return !$this->validate_contains_list($field, $input, $params);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_doesnt_contain_list(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_doesnt_contain_list($field, $input, $params);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_boolean(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_boolean($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_valid_email(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_valid_email($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_max_len(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_max_len($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_min_len(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_min_len($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_exact_len(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_exact_len($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_between_len(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_between_len($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_alpha(string $field, array $input, array $params = [], mixed $value = null): bool
    {
        return !$this->validate_alpha($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_alpha_numeric(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_alpha_numeric($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_alpha_dash(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_alpha_dash($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_alpha_numeric_dash(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_alpha_numeric_dash($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_alpha_numeric_space(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_alpha_numeric_space($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_alpha_space(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_alpha_space($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_numeric(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_numeric($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_integer(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_integer($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_float(string $field, array $input, array $params = [], mixed $value = null): bool
    {
        return !$this->validate_float($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_valid_url(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_valid_url($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_url_exists(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_url_exists($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_valid_ip(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_valid_ip($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_valid_ipv4(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_valid_ipv4($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_valid_ipv6(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_valid_ipv6($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_valid_cc(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_valid_cc($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_valid_name(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_valid_name($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_street_address(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_street_address($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_iban(string $field, array $input, array $params = [], mixed $value = null): bool
    {
        return !$this->validate_iban($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_date(string $field, array $input, array $params = [], mixed $value = null): bool
    {
        return !$this->validate_date($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_min_age(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_min_age($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_max_numeric(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_max_numeric($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_min_numeric(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_min_numeric($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_starts(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_starts($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_required_file(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_required_file($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_extension(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_extension($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_equalsfield(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_equalsfield($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_guidv4(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_guidv4($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_phone_number(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_phone_number($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_regex(string $field, array $input, array $params = [], mixed $value = null): bool
    {
        return !$this->validate_regex($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_valid_json_string(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_valid_json_string($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_valid_array_size_greater(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_valid_array_size_greater($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_valid_array_size_lesser(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_valid_array_size_lesser($field, $input, $params, $value);
    }

    /**
     * Invert with.
     *
     * @param string             $field
     * @param array<int, string> $input
     * @param array<int, string> $params
     * @param mixed              $value
     *
     * @return bool
     */
    protected function validate_invert_valid_array_size_equal(
        string $field,
        array $input,
        array $params = [],
        mixed $value = null
    ): bool {
        return !$this->validate_valid_array_size_equal($field, $input, $params, $value);
    }
}
