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

use Closure;
use Omega\View\AbstractTemplatorParse;
use Omega\View\Exceptions\DirectiveCanNotBeRegisterException;
use Omega\View\Exceptions\DirectiveNotRegisterException;

use function array_key_exists;
use function array_map;
use function explode;
use function implode;
use function ltrim;
use function preg_replace_callback;

/**
 * Handles custom template directives.
 *
 * This templator allows registering and invoking user-defined directives inside templates.
 * Directives are translated into PHP code at parse time, except for those reserved by
 * the core templators listed in the exclude list.
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
class DirectiveTemplator extends AbstractTemplatorParse
{
    /** @var array<string, Closure> Registered custom directives mapped to their handler closures. */
    private static array $directive = [];

    /** @var array<string, string> List of reserved directive names mapped to their core templator classes. */
    public static array $excludeList = [
        'break'     => BreakTemplator::class,
        'component' => ComponentTemplator::class,
        'continue'  => ContinueTemplator::class,
        'else'      => IfTemplator::class,
        'extend'    => SectionTemplator::class,
        'foreach'   => EachTemplator::class,
        'if'        => IfTemplator::class,
        'include'   => IncludeTemplator::class,
        'bool'      => BooleanTemplator::class,
        'json'      => JsonTemplator::class,
        'php'       => PHPTemplator::class,
        'raw'       => NameTemplator::class,
        'section'   => SectionTemplator::class,
        'set'       => SetTemplator::class,
        'use'       => UseTemplator::class,
        'yield'     => SectionTemplator::class,
    ];

    /**
     * Registers a new custom directive.
     *
     * The directive name must not conflict with any reserved directive handled
     * by the core templators.
     *
     * @param string  $name     The directive name.
     * @param Closure $callable The callback executed when the directive is invoked.
     * @return void
     * @throws DirectiveCanNotBeRegisterException If the directive name is reserved.
     */
    public static function register(string $name, Closure $callable): void
    {
        if (array_key_exists($name, self::$excludeList)) {
            throw new DirectiveCanNotBeRegisterException($name, self::$excludeList[$name]);
        }

        self::$directive[$name] = $callable;
    }

    /**
     * Calls a registered directive.
     *
     * Executes the directive callback with the given parameters and returns
     * its output as a string.
     *
     * @param string $name        The directive name.
     * @param mixed  ...$parameters Parameters passed to the directive callback.
     * @return string The rendered directive output.
     * @throws DirectiveNotRegisterException If the directive is not registered.
     */
    public static function call(string $name, mixed ...$parameters): string
    {
        if (false === array_key_exists($name, self::$directive)) {
            throw new DirectiveNotRegisterException($name);
        }

        $callback = self::$directive[$name];

        return (string) $callback(...$parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function parse(string $template): string
    {
        return preg_replace_callback(
            '/{%\s*(\w+)\((.*?)\)\s*%}/',
            function ($matches) {
                $name   = $matches[1];
                $params = explode(',', $matches[2]);
                $params = array_map(fn ($param) => ltrim($param), $params);

                return array_key_exists($name, self::$excludeList)
                    ? $matches[0]
                    : '<?php echo Omega\View\Templator\DirectiveTemplator::call(\''
                    . $name . '\', '
                    . implode(', ', $params) . '); ?>';
            },
            $template
        );
    }
}
