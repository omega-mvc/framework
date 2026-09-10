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
 * Defines a contract for templators that track template file dependencies.
 *
 * Implementations of this interface expose information about which template
 * files were involved during the parsing process, allowing the view engine
 * to determine dependency relationships, cache invalidation, or recompilation
 *
 * @category  Omega
 * @package   View
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
interface DependencyTemplatorInterface
{
    /**
     * Returns the list of template dependencies collected during parsing.
     *
     * The returned array maps template file paths to an integer representing
     * their depth, order, or usage count, depending on the templator
     * implementation.
     *
     * @return array<string, int> An associative array of template paths and their dependency metadata.
     */
    public function dependOn(): array;
}
