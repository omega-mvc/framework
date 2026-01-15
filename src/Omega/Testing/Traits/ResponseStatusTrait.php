<?php

/**
 * Part of Omega - Testing Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Testing\Traits;

use Omega\Http\Response;

/**
 * ResponseStatusTrait
 *
 * This trait provides a set of convenient assertion methods for testing HTTP response
 * status codes within test cases. It allows asserting common HTTP status codes such as
 * OK, Created, No Content, Bad Request, Unauthorized, Forbidden, Not Found, and Method Not Allowed.
 *
 * Each method internally calls `assertStatusCode` with the corresponding HTTP status code
 * and a descriptive message.
 *
 * @category   Omega
 * @package    Testing
 * @subpackage Traits
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
trait ResponseStatusTrait
{
    /**
     * Assert that the response status code is 200 OK.
     *
     * @return void
     */
    public function assertOk(): void
    {
        $this->assertStatusCode(Response::HTTP_OK, 'Response code must return ok');
    }

    /**
     * Assert that the response status code is 201 Created.
     *
     * @return void
     */
    public function assertCreated(): void
    {
        $this->assertStatusCode(Response::HTTP_CREATED, 'Response code must return create');
    }

    /**
     * Assert that the response status code is 204 No Content.
     *
     * @return void
     */
    public function assertNoContent(): void
    {
        $this->assertStatusCode(Response::HTTP_NO_CONTENT, 'Response code must return no content');
    }

    /**
     * Assert that the response status code is 400 Bad Request.
     *
     * @return void
     */
    public function assertBadRequest(): void
    {
        $this->assertStatusCode(Response::HTTP_BAD_REQUEST, 'Response code must return Bad Request');
    }

    /**
     * Assert that the response status code is 401 Unauthorized.
     *
     * @return void
     */
    public function assertUnauthorized(): void
    {
        $this->assertStatusCode(Response::HTTP_UNAUTHORIZED, 'Response code must return Unauthorized');
    }

    /**
     * Assert that the response status code is 403 Forbidden.
     *
     * @return void
     */
    public function assertForbidden(): void
    {
        $this->assertStatusCode(Response::HTTP_FORBIDDEN, 'Response code must return Forbidden');
    }

    /**
     * Assert that the response status code is 404 Not Found.
     *
     * @return void
     */
    public function assertNotFound(): void
    {
        $this->assertStatusCode(Response::HTTP_NOT_FOUND, 'Response code must return Not Found');
    }

    /**
     * Assert that the response status code is 405 Method Not Allowed.
     *
     * @return void
     */
    public function assertNotAllowed(): void
    {
        $this->assertStatusCode(Response::HTTP_METHOD_NOT_ALLOWED, 'Response code must return Not Allowed');
    }
}
