<?php

/**
 * Part of Omega - Tests\Http Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\Http\Support;

use Omega\Http\Request;
use Omega\Http\Response;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * TestKernelTerminate class.
 *
 * A test middleware/terminate handler used for kernel testing.
 * This class provides a simple terminate method that echoes the
 * request URL and the response content. It is used to verify
 * that terminate callbacks are executed correctly in the kernel.
 *
 * @category   Tests
 * @package    Http
 * @subpackage Support
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversNothing]
class TestKernelTerminate
{
    /**
     * Handles the termination of a request.
     *
     * This method is called during the application's terminate phase.
     * It receives the HTTP request and response objects and performs
     * custom termination logic, such as logging, echoing, or other
     * cleanup operations. In this test implementation, it echoes
     * the request URL and the response content.
     *
     * @param Request $request  The HTTP request object being terminated.
     * @param Response $response The HTTP response object being terminated.
     * @return void
     */
    public function terminate(Request $request, Response $response): void
    {
        echo $request->getUrl();
        echo $response->getContent();
    }
}
