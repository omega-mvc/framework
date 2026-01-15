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

namespace Omega\View\Exceptions;

/**
 * Exception thrown when a yield section is referenced but not defined in a template.
 *
 * Indicates that a placeholder section for content injection is missing.
 *
 * @category   Omega
 * @package    View
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class YeldSectionNotFoundException extends AbstractViewException
{
    /**
     * Constructs a new YeldSectionNotFoundException.
     *
     * @param string $fileName The name of the view file where the missing yield section was expected.
     */
    public function __construct(string $fileName)
    {
        parent::__construct('Yield section not found: `%s`', $fileName);
    }
}
