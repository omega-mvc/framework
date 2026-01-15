<?php

/**
 * Part of Omega - Template Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection */

declare(strict_types=1);

namespace Omega\Template;

/**
 * Maintains a collection of Property objects.
 *
 * This class acts as a registry for multiple property definitions.
 * It provides helper methods to create, store, and retrieve Property instances.
 *
 * @category  Omega
 * @package   Template
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class PropertyPool
{
    /** @var Property[] Internal list of stored Property instances. */
    private array $pools = [];

    /**
     * Creates a new Property instance, stores it in the pool, and returns it.
     *
     * @param string $name The property name.
     *
     * @return Property The newly created property instance.
     */
    public function name(string $name): Property
    {
        return $this->pools[] = new Property($name);
    }

    /**
     * Returns all property instances managed by the pool.
     *
     * @return Property[] An array of stored Property objects.
     */
    public function getPools(): array
    {
        return $this->pools;
    }
}
