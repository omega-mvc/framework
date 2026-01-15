<?php

/**
 * Part of Omega - View Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\View;

/**
 * Class Portal
 *
 * A read-only data container for portal items.
 * Provides controlled access to its properties via magic getter and utility methods.
 *
 * This class stores an associative array of items and exposes them as read-only
 * properties. It is useful for passing around structured configuration or state
 * data without allowing modification.
 *
 * @category  Omega
 * @package   View
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
readonly class Portal
{
    /**
     * Portal items container.
     *
     * @param array<string, mixed> $items Associative array of portal items.
     */
    public function __construct(private array $items)
    {
    }

    /**
     * Get a portal item by name.
     *
     * Provides read-only access to the items stored in the portal.
     *
     * @param string $name Name of the item to retrieve.
     * @return mixed|null Returns the item value if it exists, or null otherwise.
     */
    public function __get(string $name): mixed
    {
        return $this->items[$name] ?? null;
    }

    /**
     * Check whether a portal item exists.
     *
     * @param string $name Name of the item to check.
     * @return bool True if the item exists, false otherwise.
     */
    public function has(string $name): bool
    {
        return isset($this->items[$name]);
    }
}
