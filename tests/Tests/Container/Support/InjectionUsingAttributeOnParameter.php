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

/** @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection */

declare(strict_types=1);

namespace Tests\Container\Support;

use Omega\Container\Attribute\Inject;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Demonstrates injection using the Inject attribute on a method parameter.
 *
 * The container resolves the parameter value directly from the container
 * using the provided key.
 *
 * @category   Tests
 * @package    Container
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversNothing]
class InjectionUsingAttributeOnParameter
{
    /** @var string Injected dependency value */
    public string $dependency;

    /**
     * Injects the dependency resolved from the container into the parameter.
     *
     * @param string $dependency Resolved dependency value.
     * @return void
     */
    #[Inject]
    public function setDependency(#[Inject('db.host')] string $dependency): void
    {
        $this->dependency = $dependency;
    }
}
