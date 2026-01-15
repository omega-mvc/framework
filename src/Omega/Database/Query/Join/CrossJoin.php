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
 * Class CrossJoin
 *
 * Represents a SQL CROSS JOIN operation.
 * Generates a CROSS JOIN clause for the query without any ON condition.
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
class CrossJoin extends AbstractJoin
{
    /**
     * Build the CROSS JOIN SQL statement.
     *
     * @return string SQL CROSS JOIN clause
     */
    protected function joinBuilder(): string
    {
        return "CROSS JOIN {$this->getAlias()}";
    }
}
