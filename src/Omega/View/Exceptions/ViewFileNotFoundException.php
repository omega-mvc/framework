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
 * Exception thrown when a view file cannot be found.
 *
 * Indicates that the specified template file does not exist or is not accessible.
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
class ViewFileNotFoundException extends AbstractViewException
{
    /**
     * Constructs a new ViewFileNotFoundException.
     *
     * @param string $fileName The path or name of the view file that was not found.
     */
    public function __construct(string $fileName)
    {
        parent::__construct('View file not found: `%s`', $fileName);
    }
}
