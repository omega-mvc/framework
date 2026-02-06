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
 * Demonstrates multiple setter injection on the same class.
 *
 * Each setter method is resolved and invoked independently by the container.
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
class MultipleSetterClass
{
    /** @var DependencyClass|null First injected dependency */
    public ?DependencyClass $dependency1 = null;

    /** @var AnotherService|null Second injected dependency */
    public ?AnotherService $dependency2 = null;

    /**
     * Injects the first dependency.
     *
     * @param DependencyClass $dependency Resolved dependency.
     * @return void
     */
    #[Inject]
    public function setDependency1(DependencyClass $dependency): void
    {
        $this->dependency1 = $dependency;
    }

    /**
     * Injects the second dependency.
     *
     * @param AnotherService $anotherService Resolved service.
     * @return void
     */
    #[Inject]
    public function setDependency2(AnotherService $anotherService): void
    {
        $this->dependency2 = $anotherService;
    }
}
