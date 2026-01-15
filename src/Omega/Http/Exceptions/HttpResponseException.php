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

use Omega\Http\Response;
use RuntimeException;

/**
 * Exception used to return a Response object directly.
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
class HttpResponseException extends RuntimeException
{
    /** @var Response The HTTP response associated with this exception.
     * @noinspection PhpGetterAndSetterCanBeReplacedWithPropertyHooksInspection
     */
    protected Response $response;

    /**
     * Create a new HttpResponseException instance.
     *
     * @param Response $response The HTTP response to return.
     * @return void
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }

    /**
     * Get the HTTP response associated with this exception.
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}
