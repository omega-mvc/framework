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

namespace Omega\Database\Schema\Table;

use Omega\Database\Schema\Query;
use Omega\Database\Schema\SchemaConnectionInterface;

/**
 * Class Raw
 *
 * Handles execution of a raw SQL query string.
 *
 * @category   Omega
 * @package    Database
 * @subpackage Schema\Table
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class Raw extends Query
{
    /** @var string Raw SQL query */
    private string $raw;

    /**
     * Raw constructor.
     *
     * @param string                    $raw SQL query string
     * @param SchemaConnectionInterface $pdo Database connection interface
     */
    public function __construct(string $raw, SchemaConnectionInterface $pdo)
    {
        $this->raw = $raw;
        $this->pdo = $pdo;
    }

    /**
     * Return the raw SQL query string.
     *
     * @return string SQL query
     */
    protected function builder(): string
    {
        return $this->raw;
    }
}
