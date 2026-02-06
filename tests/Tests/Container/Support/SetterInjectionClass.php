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

use Omega\Container\Attribute\Inject;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Class using setter-based dependency injection.
 *
 * Used to verify that methods annotated with Inject are correctly invoked
 * by the container.
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
class SetterInjectionClass
{
    /** @var mixed Injected dependency instance. */
    public mixed $dependency;

    /**
     * Setter method for dependency injection.
     *
     * @param DependencyClass $dependency
     * @return void
     */
    #[Inject]
    public function setDependency(DependencyClass $dependency): void
    {
        $this->dependency = $dependency;
    }
}
