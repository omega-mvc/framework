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

use Countable;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * Fixture class with an intersection-typed constructor dependency.
 *
 * Intersection types are not supported by the dependency resolver, so
 * building this class must raise a {@see \Omega\Container\Exceptions\BindingResolutionException}.
 *
 * @category   Tests
 * @package    Container
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversNothing]
final class IntersectionTypeConstructorClass
{
    /**
     * @param Countable&UnresolvableInterface $dependency Intersection-typed dependency.
     */
    public function __construct(public Countable & UnresolvableInterface $dependency)
    {
    }
}