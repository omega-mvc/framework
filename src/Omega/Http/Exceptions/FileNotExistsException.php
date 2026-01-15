<?php

/**
 * Part of Omega - Http Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Http\Exceptions;

use InvalidArgumentException;

use function sprintf;

/**
 * Exception thrown when a given file does not exist.
 *
 * @category   Omega
 * @package    Http
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class FileNotExistsException extends InvalidArgumentException
{
    /**
     * Create a new FileNotExistsException instance.
     *
     * @param string $fileLocation The path of the file that was not found.
     * @return void
     */
    public function __construct(string $fileLocation)
    {
        parent::__construct(
            sprintf(
                'File location not exists `%s`',
                $fileLocation
            )
        );
    }
}
