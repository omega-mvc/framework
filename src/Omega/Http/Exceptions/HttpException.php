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

/** @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection */

declare(strict_types=1);

namespace Omega\Http\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Generic HTTP Exception.
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
class HttpException extends RuntimeException
{
    /** @var int HTTP status code. */
    private int $statusCode;

    /** @var array<string, string> HTTP headers associated with the exception. */
    private array $headers;

    /**
     * Create a new HttpException instance.
     *
     * @param int                   $statusCode HTTP status code.
     * @param string                $message    Exception message.
     * @param Throwable|null        $previous   Previous exception for chaining.
     * @param array<string, string> $headers    HTTP headers to attach to the exception.
     * @param int                   $code       Internal exception code.
     * @return void
     */
    public function __construct(
        int $statusCode,
        string $message,
        ?Throwable $previous = null,
        array $headers = [],
        int $code = 0,
    ) {
        $this->statusCode = $statusCode;
        $this->headers    = $headers;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the current HTTP status code.
     *
     * @return int Return the current HTT status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Get the HTTP headers associated with this exception.
     *
     * @return array<string, string> Return thr HTTP headers associated with this exception.
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
