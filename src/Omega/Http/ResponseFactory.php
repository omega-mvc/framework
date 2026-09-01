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

use JsonException;
use Psr\Http\Message\ResponseFactoryInterface as PsrResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

use function is_array;
use function json_encode;

use const JSON_NUMERIC_CHECK;
use const JSON_THROW_ON_ERROR;

/**
 * Native adapter that converts an Omega {@see Response} into a
 * PSR-7 {@see ResponseInterface}.
 *
 * This responder bridges the framework's decoupled HTTP response model
 * with any PSR-7 implementation (e.g. nyholm/psr7, guzzlehttp/psr7)
 * and PSR-17 factories, allowing the response to be handed to PSR-7
 * aware servers such as RoadRunner.
 *
 * @category  Omega
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
final class ResponseFactory
{
    /**
     * Convert an Omega response into a PSR-7 response.
     *
     * @param Response             $response        The Omega response to convert.
     * @param PsrResponseFactory   $responseFactory PSR-17 factory used to build the response.
     * @param StreamFactoryInterface $streamFactory   PSR-17 factory used to build the body stream.
     * @return ResponseInterface The converted PSR-7 response.
     * @throws JsonException If the array content cannot be encoded to JSON.
     */
    public static function toPsr7(
        Response $response,
        PsrResponseFactory $responseFactory,
        StreamFactoryInterface $streamFactory
    ): ResponseInterface {
        $statusCode   = $response->getStatusCode();
        $reasonPhrase = Response::$statusTexts[$statusCode] ?? '';
        $content      = $response->getContent();

        $body = is_array($content)
            ? (string) json_encode($content, JSON_NUMERIC_CHECK | JSON_THROW_ON_ERROR)
            : $content;

        $psr7 = $responseFactory
            ->createResponse($statusCode, $reasonPhrase)
            ->withProtocolVersion($response->getProtocolVersion())
            ->withBody($streamFactory->createStream($body));

        foreach ($response->headers->toArray() as $name => $value) {
            $psr7 = $psr7->withHeader($name, (string) $value);
        }

        return $psr7;
    }
}
