<?php

/**
 * Part of Omega - Security Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Security\Exceptions;

use RuntimeException;

/**
 * Exception thrown when Argon2id hashing is not supported on the current PHP environment.
 *
 * This typically occurs if the PHP build does not include Argon2id support or if
 * password hashing with PASSWORD_ARGON2ID fails for any reason.
 *
 * Users can catch this exception to provide alternative hashing mechanisms
 * or to log errors when Argon2id hashing cannot be performed.
 *
 * @category   Omega
 * @package    Security
 * @subpackage Exceptions
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class Argon2IdHashingNotSupportedException extends RuntimeException implements SecurityExceptionInterface
{
}
