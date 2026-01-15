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
 * Interface defining a rule-based contract for color objects.
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
interface RuleInterface
{
    /**
     * Get the list of integer rules applied.
     *
     * @return array<int, int> Array of rule codes
     */
    public function getRule(): array;

    /**
     * Set the rules for this color object.
     *
     * @param array<int, int>|null $rule Array of rule codes or null to reset
     */
    public function setRule(?array $rule = []): void;

    /**
     * Check if a specific rule exists in the current set.
     *
     * @param int $rule Rule code to check
     * @return bool True if the rule exists, false otherwise
     */
    public function hasRule(int $rule): bool;

    /**
     * Clear all rules from this color object.
     */
    public function clearRule(): void;

    /**
     * Return the raw rules as a semicolon-delimited string.
     *
     * @return string Raw string representation of rules
     */
    public function rawRule(): string;
}
