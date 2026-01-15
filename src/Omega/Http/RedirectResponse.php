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

use Exception;

use function htmlspecialchars;

use const ENT_QUOTES;

/**
 * HTTP response representing a redirect.
 *
 * This response automatically sets the `Location` header
 * and generates an HTML page with a meta-refresh redirect.
 * Can be used to redirect the client to a new URL with a
 * specified HTTP status code (default 302).
 *
 * @category  Omega
 * @package   Http
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class RedirectResponse extends Response
{
    /**
     * Create a new redirect response.
     *
     * Sets the HTTP status code and the target URL for redirection.
     * The response content contains a simple HTML page with a
     * meta-refresh and a clickable link.
     *
     * @param string $url           The target URL to redirect to.
     * @param int    $responseCode  The HTTP status code for the redirect (default 302).
     * @param array<string, string> $headers Optional additional HTTP headers.
     * @return void
     * @throws Exception If an error occurs while setting the response content or headers.
     */
    public function __construct(string $url, int $responseCode = 302, array $headers = [])
    {
        parent::__construct('', $responseCode, $headers);

        $this->setTarget($url);
    }

    /**
     * Set the target URL for the redirect.
     *
     * Updates both the `Location` header and the HTML content
     * of the response to point to the specified URL.
     *
     * @param string $url The URL to redirect the client to.
     * @return void
     * @throws Exception If an error occurs while setting the content or headers.
     */
    public function setTarget(string $url): void
    {
        $this->setContent(
            sprintf(
                '<html lang="en">
                            <head>
                                <meta charset="UTF-8" />
                                <meta http-equiv="refresh" content="0;url=\'%1$s\'" />
                                    <title>Redirecting to %1$s</title>
                            </head>
                            <body>
                                Redirecting to <a href="%1$s">%1$s</a>.
                            </body>
                        </html>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            )
        );
        $this->setHeaders([
            'Location' => $url
        ]);
    }
}
