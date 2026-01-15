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
use Omega\Text\Str;
use Omega\View\AbstractTemplatorParse;
use Omega\View\DependencyTemplatorInterface;
use Omega\View\InteractWithCacheTrait;

use function array_key_exists;
use function explode;
use function htmlspecialchars;
use function preg_match;
use function preg_replace_callback;
use function sprintf;
use function str_replace;
use function trim;

use const PHP_EOL;

/**
 * Handles template inheritance through sections and layout extension.
 *
 * This templator processes layout extension directives, collects defined sections,
 * and injects them into the parent layout using yield placeholders. It supports
 * inline sections, block sections, grouped section definitions, default values,
 * and content fallbacks, while tracking template dependencies.
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
class SectionTemplator extends AbstractTemplatorParse implements DependencyTemplatorInterface
{
    use InteractWithCacheTrait;

    /** @var array<string, mixed> Stores resolved section contents indexed by section name. */
    private array $section = [];

    /** @var array<string, string> Cached contents of resolved template files. */
    private static array $cache = [];

    /** @var array<string, int> Tracks template dependencies and their nesting depth. */
    private array $dependOn = [];

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
     * @throws Exception If the layout template is missing, invalid, or if yield rules are violated.
     * @noinspection PhpRegExpRedundantModifierInspection
     */
    public function parse(string $template): string
    {
        self::$cache = [];

        preg_match('/{%\s*extend\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*%}/', $template, $matchesLayout);
        if (!array_key_exists(1, $matchesLayout)) {
            return $template;
        }

        if (false === $this->finder->exists($matchesLayout[1])) {
            throw new Exception('Template file not found: ' . $matchesLayout[1]);
        }

        $templatePath = $this->finder->find($matchesLayout[1]);
        $layout       = $this->getContents($templatePath);

        // add parent dependency
        $this->dependOn[$templatePath] = 1;

        // Process all sections first
        $template = preg_replace_callback(
            '/{%\s*section\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*[\'"]([^\'"]+)[\'"]\s*\)\s*%}/s',
            fn ($matches) => $this->section[$matches[1]] = htmlspecialchars(trim($matches[2])),
            $template
        );

        $template = preg_replace_callback(
            '/{%\s*section\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)\s*%}(.*?){%\s*endsection\s*%}/s',
            fn ($matches) => $this->section[$matches[1]] = trim($matches[2]),
            $template
        );

        /** @noinspection PhpUnusedLocalVariableInspection */
        $template = preg_replace_callback(
            '/{%\s*sections\s*\\s*%}(.*?){%\s*endsections\s*%}/s',
            function ($matches) {
                $lines = explode(PHP_EOL, str_replace(["\r\n", "\r", "\n"], PHP_EOL, $matches[1]));
                foreach ($lines as $line) {
                    if (Str::contains($line, ':')) {
                        $current                           = explode(':', trim($line));
                        $this->section[trim($current[0])] = trim($current[1]);
                    }
                }

                return '';
            },
            $template
        );

        // yield section
        return preg_replace_callback(
            /* phpcs:disable Generic.Files.LineLength.TooLong */
            '/{%\s*yield(?:\s*\(\s*[\'"](\w+)[\'"](?:\s*,\s*([\'\"].*?[\'\"]|null))?\s*\))?\s*%}(?:(.*?){%\s*endyield\s*%})?/s',
            /**
             * @param string[] $matches
             * @throws Exception
             */
            function (array $matches) use ($matchesLayout): string {
                if (isset($matches[2]) && '' != $matches[2] && isset($matches[3])) {
                    throw new Exception('The yield statement cannot have both a default value and content.');
                }

                // yield with given section
                if (isset($matches[1]) && array_key_exists($matches[1], $this->section)) {
                    return $this->section[$matches[1]];
                }

                // yield with default value
                if (isset($matches[3])) {
                    return trim($matches[3]);
                }

                // yield with default parameter
                if (isset($matches[2])) {
                    return trim($matches[2], '\'"');
                }

                if (isset($matches[1])) {
                    throw new Exception(
                        sprintf(
                            "Slot with extends '%s' required '%s'",
                            $matchesLayout[1],
                            $matches[1]
                        )
                    );
                }

                return '';
            },
            $layout
        );
    }
}
