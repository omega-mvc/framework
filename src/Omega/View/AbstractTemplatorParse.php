<?php

/**
 * Part of Omega - View Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\View;

/**
 * Base class for all template parsers.
 *
 * This abstract class defines the common contract and shared state for
 * all templator implementations. Each concrete templator is responsible
 * for transforming specific template directives into executable PHP code.
 *
 * A templator receives the raw template content and returns a transformed
 * version of it, potentially resolving directives, injecting PHP logic,
 * and tracking template dependencies.
 *
 * The parsing process is intentionally stateless between executions:
 * implementations are expected to reset any internal caches at the
 * beginning of each {@see parse()} call.
 *
 * @category  Omega
 * @package   View
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
abstract class AbstractTemplatorParse
{
    /**
     * List of PHP `use` statements collected during template parsing.
     *
     * This array is populated by templators that support import directives
     * and is later injected at the beginning of the compiled template.
     *
     * @var string[]
     */
    protected array $uses = [];

    /**
     * Create a new templator instance.
     *
     * @param TemplatorFinder $finder   Resolves template file paths and checks their existence.
     * @param string          $cacheDir Directory used to store compiled or cached templates.
     */
    final public function __construct(
        protected TemplatorFinder $finder,
        protected string $cacheDir
    ) {
    }

    /**
     * Parse the given template content.
     *
     * Implementations must transform the input template by resolving
     * supported directives and returning valid PHP-compatible output.
     *
     * Any internal or static cache used by the templator should be reset
     * at the beginning of the parsing process to avoid stale state.
     *
     * @param string $template The raw template content to be parsed.
     * @return string The parsed template with all supported directives resolved.
     */
    abstract public function parse(string $template): string;
}
