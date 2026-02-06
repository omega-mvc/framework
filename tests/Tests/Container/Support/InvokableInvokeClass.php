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

namespace Tests\Container\Support;

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Invokable class with a dependency resolved by the container.
 *
 * The dependency is injected into the __invoke method.
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
class InvokableInvokeClass
{
    /** @var DependencyClass Injected dependency instance */
    public DependencyClass $dep;

    /**
     * Invokes the object with a resolved dependency.
     *
     * @param DependencyClass $dep Resolved dependency.
     * @return string Invocation result.
     */
    public function __invoke(DependencyClass $dep): string
    {
        $this->dep = $dep;

        return 'invoked';
    }
}
