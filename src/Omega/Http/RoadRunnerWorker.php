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

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * RoadRunner persistent-worker HTTP loop.
 *
 * Bridges the Omega HTTP kernel to RoadRunner. Each incoming PSR-7 server
 * request is converted to an Omega `Request` via `RequestFactory`, handled by
 * the `Http` kernel, converted back to a PSR-7 response and sent to the client.
 * The `Http::terminate()` call then performs the per-request reset so the
 * worker can safely service the next request.
 *
 * RoadRunner (spiral/roadrunner-http) and a PSR-7 implementation are
 * application-level dependencies; Omega itself stays decoupled from them.
 *
 * @category  Omega
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class RoadRunnerWorker
{
    /**
     * The Omega HTTP kernel handling each request.
     *
     * @var Http
     */
    protected Http $http;

    /**
     * Converts an Omega Response to a PSR-7 ResponseInterface.
     *
     * @var Closure(Response): ResponseInterface
     */
    protected Closure $responseFactory;

    /**
     * Create a new RoadRunner worker.
     *
     * @param Http     $http            The Http kernel.
     * @param Closure  $responseFactory Maps an Omega Response to a PSR-7 ResponseInterface.
     *                                  The application supplies its own PSR-7 implementation.
     */
    public function __construct(Http $http, Closure $responseFactory)
    {
        $this->http = $http;
        $this->responseFactory = $responseFactory;
    }

    /**
     * Run the persistent request/response loop.
     *
     * The responder MUST expose `waitRequest()` and `respond(...)` methods.
     * The canonical implementation is `Spiral\RoadRunner\Http\PSR7Worker`.
     *
     * @param object $responder RoadRunner PSR-7 worker (waitRequest/respond).
     * @return void
     */
    public function run(object $responder): void
    {
        while (($request = $responder->waitRequest()) !== null) {
            if ($request === false) {
                break;
            }

            $omegaRequest  = null;
            $omegaResponse = null;

            try {
                $omegaRequest  = $this->makeRequest($request);
                $omegaResponse = $this->http->handle($omegaRequest);
                $responder->respond(($this->responseFactory)($omegaResponse));
            } catch (Throwable $th) {
                $responder->respond($this->errorResponse($th));
            } finally {
                if ($omegaRequest !== null && $omegaResponse !== null) {
                    $this->http->terminate($omegaRequest, $omegaResponse);
                }
            }
        }
    }

    /**
     * Convert a PSR-7 server request into an Omega Request.
     *
     * @param ServerRequestInterface $request The PSR-7 server request.
     * @return Request The equivalent Omega Request.
     */
    protected function makeRequest(ServerRequestInterface $request): Request
    {
        return RequestFactory::fromPsr7ServerRequest($request);
    }

    /**
     * Build a PSR-7 error response for exceptions escaping the kernel.
     *
     * @param Throwable $th The thrown exception.
     * @return ResponseInterface
     */
    protected function errorResponse(Throwable $th): ResponseInterface
    {
        try {
            $content = $th->getMessage();
        } catch (Throwable) {
            $content = 'Internal Server Error';
        }

        return ($this->responseFactory)(new Response($content, 500));
    }
}
