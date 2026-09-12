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

namespace Omega\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface RoadRunnerResponderInterface
 *
 * Contract for the RoadRunner PSR-7 responder used by {@see RoadRunnerWorker}.
 *
 * The canonical implementation is `Spiral\RoadRunner\Http\PSR7Worker`.
 *
 * @category  Omega
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
interface RoadRunnerResponderInterface
{
    /**
     * Wait for the next incoming request.
     *
     * @return ServerRequestInterface|false|null
     *         The PSR-7 server request, `false` to stop the worker, or `null`
     *         when there is no request available yet.
     */
    public function waitRequest(): ServerRequestInterface|false|null;

    /**
     * Send a PSR-7 response back to the client.
     *
     * @param ResponseInterface $response The PSR-7 response to send.
     * @return void
     */
    public function respond(ResponseInterface $response): void;
}
