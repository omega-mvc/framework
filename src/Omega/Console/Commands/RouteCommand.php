<?php

/**
 * Part of Omega - Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Style\Alert;
use Omega\Console\Style\Style;
use Omega\Console\Traits\PrintHelpTrait;
use Omega\Router\Router;
use Omega\Text\Str;

use function count;
use function is_array;
use function Omega\Console\style;

/**
 * RouteCommand
 *
 * Console command that lists all registered application routes.
 * It formats and displays route information such as HTTP methods,
 * route names, and URI expressions in a readable, styled output.
 *
 * The command is intended for inspection and debugging purposes,
 * providing a quick overview of the routing configuration.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
class RouteCommand extends AbstractCommand
{
    use PrintHelpTrait;

    /**
     * Command registration configuration.
     *
     * Defines the pattern used to invoke the command and the method to execute.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $command = [
        [
            'cmd' => 'route:list',
            'fn'  => [RouteCommand::class, 'main'],
        ],
    ];

    /**
     * Returns a description of the command, its options, and their relations.
     *
     * This is used to generate help output for users.
     *
     * @return array<string, array<string, string|string[]>>
     */
    public function printHelp(): array
    {
        return [
            'commands'  => [
                'route:list' => 'Get route list information',
            ],
            'options'   => [],
            'relation'  => [],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return int Exit code: always 0.
     */
    public function main(): int
    {
        $print = new Style();
        $print->tap(Alert::render()->success('Route List'));
        /** @noinspection PhpUnusedLocalVariableInspection */
        foreach (Router::getRoutes() as $key => $route) {
            $method = $this->methodToStyle($route['method']);
            $name   = style($route['name'])->textWhite();
            $length = $method->length() + $name->length();

            $print
              ->tap($method)
              ->push(' ')
              ->tap($name)
              ->repeat('.', 80 - $length)->textDim()
              ->push(' ')
              ->push(Str::limit($route['expression'], 30))
              ->newLines()
            ;
        }
        $print->out();

        return 0;
    }

    /**
     * Convert one or more HTTP methods into a styled output representation.
     *
     * When multiple methods are provided, they are grouped together
     * and separated by a visual delimiter.
     *
     * @param string|string[] $methods One or more HTTP methods to format
     * @return Style Styled representation of the HTTP method(s)
     */
    private function methodToStyle(array|string $methods): Style
    {
        if (is_array($methods)) {
            $group  = new Style();
            $length = count($methods);
            for ($i = 0; $i < $length; $i++) {
                $group->tap($this->coloringMethod($methods[$i]));
                if ($i < $length - 1) {
                    $group->push('|')->textDim();
                }
            }

            return $group;
        }

        return $this->coloringMethod($methods);
    }

    /**
     * Apply color styling to a single HTTP method based on its type.
     *
     * Common HTTP verbs are color-coded to improve readability
     * in console output, while unknown methods use a dim style.
     *
     * @param string $method HTTP method name
     * @return Style Styled HTTP method output
     */
    private function coloringMethod(string $method): Style
    {
        $method = strtoupper($method);

        if ($method === 'GET') {
            return new Style($method)->textBlue();
        }

        if ($method === 'POST' || $method === 'PUT') {
            return new Style($method)->textYellow();
        }

        if ($method === 'DELETE') {
            return new Style($method)->textRed();
        }

        return new Style($method)->textDim();
    }
}
