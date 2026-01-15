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
 * A container for collecting multiple Constant objects during class generation.
 *
 * This pool provides a fluent interface for creating and storing constants,
 * typically used by generators that need to handle a batch of constant
 * definitions before rendering them in sequence.
 *
 * @category  Omega
 * @package   Template
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class ConstPool
{
    /**
     * The list of constants stored in the pool.
     *
     * @var Constant[]
     */
    private array $pools = [];

    /**
     * Creates a new constant, adds it to the pool, and returns it.
     *
     * @param string $name The name of the constant to create.
     * @return Constant The newly created constant instance.
     */
    public function name(string $name): Constant
    {
        return $this->pools[] = new Constant($name);
    }

    /**
     * Returns all constant instances stored in the pool.
     *
     * @return Constant[] The list of created constants.
     */
    public function getPools(): array
    {
        return $this->pools;
    }
}
