<?php

/**
 * Part of Omega - Database Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Database\Schema\Traits;

/**
 * Trait ConditionTrait
 *
 * Provides optional SQL conditions for CREATE or DROP statements:
 * - IF EXISTS
 * - IF NOT EXISTS
 *
 * Can be used in classes handling database or table schema operations
 * to toggle existence checks.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Schema\Traits
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
trait ConditionTrait
{
    /**
     * @var string SQL condition for existence checks (e.g., "IF EXISTS" or "IF NOT EXISTS")
     */
    private string $ifExists = '';

    /**
     * Set the "IF EXISTS" or "IF NOT EXISTS" condition for SQL statements.
     *
     * @param bool $value Whether to use IF EXISTS (true) or IF NOT EXISTS (false)
     * @return self Fluent interface
     */
    public function ifExists(bool $value = true): self
    {
        $this->ifExists = $value
            ? 'IF EXISTS'
            : 'IF NOT EXISTS';

        return $this;
    }

    /**
     * Set the "IF NOT EXISTS" or "IF EXISTS" condition for SQL statements.
     *
     * @param bool $value Whether to use IF NOT EXISTS (true) or IF EXISTS (false)
     * @return self Fluent interface
     */
    public function ifNotExists(bool $value = true): self
    {
        $this->ifExists = $value
            ? 'IF NOT EXISTS'
            : 'IF EXISTS';

        return $this;
    }
}
