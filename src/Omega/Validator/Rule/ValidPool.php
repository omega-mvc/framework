<?php

declare(strict_types=1);

namespace Omega\Validator\Rule;

final class ValidPool
{
    /** @var array<int, array{field: string, rule: Valid}> */
    private array $pool = [];

    /**
     * Get entry valid rule.
     *
     * @return Valid[] Valid rule
     */
    public function getPool(): array
    {
        $pool = [];

        foreach ($this->pool as $ruler) {
            $key = $ruler['field'];
            $pool[$key] = ($pool[$key] ?? new Valid())->combine($ruler['rule']);
        }

        return $pool;
    }

    /**
     * Filter validation only allow field.
     *
     * @param array<int, string> $fields Fields allow to validation
     *
     * @return self
     */
    public function only(array $fields): self
    {
        $this->pool = array_filter(
            $this->pool,
            fn (array $field): bool => in_array($field['field'], $fields)
        );

        return $this;
    }

    /**
     * Filter validation expect allow field.
     *
     * @param array<int, string> $fields Fields allow to validation
     */
    public function except(array $fields): self
    {
        $this->pool = array_filter(
            $this->pool,
            fn (array $field): bool => !in_array($field['field'], $fields)
        );

        return $this;
    }

    /**
     * Combine validation rule with other validation rule.
     *
     * @param ValidPool $validPool ValidPool class to combine
     *
     * @return self
     */
    public function combine(ValidPool $validPool): self
    {
        $this->pool = array_merge($this->pool, $validPool->pool);

        return $this;
    }

    /**
     * Add new valid rule.
     *
     * @param string $field Field name
     *
     * @return Valid New rule Validation
     */
    public function rule(string ...$field): Valid
    {
        return $this->setFieldRule(new Valid(), $field);
    }

    /**
     * Add new valid rule.
     *
     * @param string $field Field name
     *
     * @return Valid New rule Validation
     */
    public function __invoke(string ...$field)
    {
        return $this->rule(...$field);
    }

    /**
     * Add new valid rule.
     *
     * @param string $name Field name
     *
     * @return Valid New rule Validation
     */
    public function __get(string $name): Valid
    {
        return $this->rule($name);
    }

    /**
     * Set new feild rule.
     *
     * @param string $name  Field name
     * @param string $value Validation Rule
     *
     * @return void
     */
    public function __set(string $name, string $value): void
    {
        $this->rule($name)->raw($value);
    }

    /**
     * Helper to add multy rule in single method.
     *
     * @param Valid                     $valid  Instans for new validation rule
     * @param array<int|string, string> $fields Fields name
     *
     * @return Valid Rule Validation base from param
     */
    private function setFieldRule(Valid $valid, array $fields): Valid
    {
        $this->pool = array_merge(
            $this->pool,
            array_values(
                array_map(
                    static fn (string $field): array => [
                        'field' => $field,
                        'rule'  => $valid,
                    ],
                    $fields,
                ),
            ),
        );

        return $valid;
    }
}
