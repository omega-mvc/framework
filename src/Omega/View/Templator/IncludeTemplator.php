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

namespace Omega\View\Templator;

use Exception;
use Omega\View\AbstractTemplatorParse;
use Omega\View\DependencyTemplatorInterface;
use Omega\View\InteractWithCacheTrait;

use function preg_replace_callback;
use function trim;

/**
 * Include templator class.
 *
 * Processes `{% include('view') %}` directives within templates.
 *
 * This templator resolves and inlines external template files, optionally
 * parsing nested includes up to a configurable maximum depth. It also tracks
 * template dependencies for cache invalidation or recompilation purposes.
 *
 * @category   Omega
 * @package    View
 * @subpackage Templator
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class IncludeTemplator extends AbstractTemplatorParse implements DependencyTemplatorInterface
{
    use InteractWithCacheTrait;

    /** @var int Maximum allowed recursive include depth to prevent infinite inclusion loops. */
    private int $makeDept = 5;

    /** @var int Current include depth during recursive parsing. */
    private int $dept = 0;

    /** @var array<string, int> Tracks included template paths and their inclusion depth. */
    private array $dependOn = [];

    /** @var array<string, string> Cached contents of already loaded template files. */
    private static array $cache = [];

    /**
     * Sets the maximum recursion depth for nested include directives.
     *
     * @param int $makeDept Maximum include depth.
     * @return $this Returns the current instance for method chaining.
     */
    public function maksDept(int $makeDept): self
    {
        $this->makeDept = $makeDept;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function dependOn(): array
    {
        return $this->dependOn;
    }

    /**
     * {@inheritdoc}
     *
     * @throws Exception If an included template file cannot be found.
     */
    public function parse(string $template): string
    {
        self::$cache = [];

        return preg_replace_callback(
            '/{%\s*include\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*%}/',
            function ($matches) {
                /**if (false === $this->finder->exists($matches[1])) {
                    throw new Exception('Template file not found: ' . $matches[1]);
                }*/

                $templatePath     = $this->finder->find($matches[1]);
                $includedTemplate = $this->getContents($templatePath);

                if ($this->makeDept === 0) {
                    return $includedTemplate;
                }

                $this->makeDept--;
                $this->dependOn[$templatePath] = ++$this->dept;

                return trim($this->parse($includedTemplate));
            },
            $template
        );
    }
}
