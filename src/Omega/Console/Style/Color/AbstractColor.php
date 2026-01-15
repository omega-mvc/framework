<?php

/**
 * Part of Omega - Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Console\Style\Color;

/**
 * Abstract base class implementing common logic for color objects
 * using a set of rules.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Style\Color
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
abstract class AbstractColor implements RuleInterface
{
    /** @var array<int, int> Current set of rules for this color */
    private array $rules = [];

    /** @var array<int, int> Current set of rules for this color
     * @noinspection PhpUnusedParameterInspection
     */
    public array $rule { // phpcs:ignore
        get {
            return $this->rules; // phpcs:ignore
        }
        set(array $value) { // phpcs:ignore
            $this->rules = $value; // phpcs:ignore
        }
    }

    /**
     * Abstract color class constructor.
     *
     * @param array<int, int>|null $rule Optional initial set of rules
     */
    public function __construct(?array $rule = [])
    {
        if ($rule !== null) {
            $this->setRule($rule);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getRule(): array
    {
        return $this->rules;
    }

    /**
     * {@inheritdoc}
     */
    public function setRule(?array $rule = []): void
    {
        if ($rule !== null) {
            $this->rules = $rule;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasRule(int $rule): bool
    {
        return in_array($rule, $this->rules, true);
    }

    /**
     * {@inheritdoc}
     */
    public function clearRule(): void
    {
        $this->rules = [];
    }

    /**
     * {@inheritdoc}
     */
    public function rawRule(): string
    {
        return implode(';', $this->rules);
    }
}
