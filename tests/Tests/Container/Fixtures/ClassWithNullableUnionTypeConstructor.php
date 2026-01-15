<?php

/**
 * Part of Omega - Tests\Container Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Container\Fixtures;

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Fixture class with a nullable union-typed constructor dependency.
 *
 * Used to verify that the container correctly handles union types and null defaults.
 *
 * @category   Tests
 * @package    Container
 * @subpackage Fixtures
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversNothing]
class ClassWithNullableUnionTypeConstructor
{
    /** @var UnionDependencyOne|UnionDependencyTwo|null Holds the resolved dependency or null. */
    public UnionDependencyOne|UnionDependencyTwo|null $dependency;

    /**
     * @param UnionDependencyOne|UnionDependencyTwo|null $dependency Optional union-typed dependency.
     */
    public function __construct(UnionDependencyOne|UnionDependencyTwo|null $dependency = null)
    {
        $this->dependency = $dependency;
    }
}
