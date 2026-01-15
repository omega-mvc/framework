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
 * Maintains a collection of Method objects.
 *
 * This class is responsible for creating, storing, and returning method
 * definitions. It functions as a method registry used by higher-level
 * code generators.
 *
 * @category  Omega
 * @package   Template
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class MethodPool
{
    /** @var Method[] Internal list of stored Method instances. */
    private array $pools = [];

    /**
     * Creates a new Method instance, stores it in the pool, and returns it.
     *
     * @param string $name The method name.
     * @return Method The newly created method instance.
     */
    public function name(string $name): Method
    {
        return $this->pools[] = new Method($name);
    }

    /**
     * Returns all method instances managed by the pool.
     *
     * @return Method[] An array of stored Method objects.
     */
    public function getPools(): array
    {
        return $this->pools;
    }
}
