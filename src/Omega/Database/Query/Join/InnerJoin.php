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

namespace Omega\Database\Query\Join;

/**
 * Class InnerJoin
 *
 * Represents a SQL INNER JOIN operation.
 * Generates an INNER JOIN clause with ON conditions based on compared columns.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Query\Join
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class InnerJoin extends AbstractJoin
{
    /**
     * Build the INNER JOIN SQL statement.
     *
     * @return string SQL INNER JOIN clause
     */
    protected function joinBuilder(): string
    {
        return "INNER JOIN " . $this->getAlias() . " ON " . $this->splitJoin();
    }
}
