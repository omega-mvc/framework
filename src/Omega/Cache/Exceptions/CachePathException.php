<?php

/**
 * Part of Omega - Cache Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Cache\Exceptions;

use Exception;
use Psr\SimpleCache\CacheException as PsrCacheExceptionInterface;

use function sprintf;

/**
 * Exception thrown when the cache directory cannot be created or is not writable.
 *
 * @category   Omega
 * @package    Cache
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class CachePathException extends Exception implements PsrCacheExceptionInterface
{
    /**
     * Create a new CachePathException instance.
     *
     * @param string $path The path to the cache directory that could not be created.
     */
    public function __construct(string $path)
    {
        parent::__construct(
            sprintf(
                'The cache directory "%s" could not be created or is not writable. '
                . 'Please ensure the path exists and has proper permissions.',
                $path
            )
        );
    }
}
