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

namespace Omega\Database\Schema\Table\Attributes\Alter;

use Omega\Database\Schema\Table\Attributes\Constraint as AttributesConstraint;

/**
 * Class Constraint
 *
 * Extended Constraint class for ALTER TABLE operations.
 * Adds support for column ordering (FIRST, AFTER column) and raw SQL overrides.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Schema\Table\Attributes\Alter
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class Constraint extends AttributesConstraint
{
    /**
     * Set column position AFTER another column.
     *
     * @param string $column Reference column name
     * @return $this
     */
    public function after(string $column): self
    {
        $this->order = "AFTER $column";

        return $this;
    }

    /**
     * Set column as the first in the table.
     *
     * @return $this
     */
    public function first(): self
    {
        $this->order = 'FIRST';

        return $this;
    }

    /**
     * Override raw SQL for ALTER operation.
     *
     * @param string $raw
     * @return $this
     */
    public function raw(string $raw): self
    {
        $this->raw = $raw;

        return $this;
    }
}
