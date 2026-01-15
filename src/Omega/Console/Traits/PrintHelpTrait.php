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

namespace Omega\Console\Traits;

use Omega\Console\Style\Style;
use Omega\Text\Str;

use function array_keys;
use function implode;
use function strlen;

/**
 * PrintHelpTrait provides helper methods to render command and option
 * descriptions in a styled console output using the Style class.
 *
 * @category   Omega
 * @package    Console
 * @subpackges Traits
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
trait PrintHelpTrait
{
    /**
     * Style options for printing help.
     *
     * @var array<string, string|int> Array of styling options:
     *  - 'margin-left': Number of spaces before printing the line.
     *  - 'column-1-min-length': Minimum width of the first column.
     */
    protected array $printHelp = [
        'margin-left'         => 2,
        'column-1-min-length' => 24,
    ];

    /**
     * Render and style command descriptions in console output.
     *
     * Each command is printed with its name, arguments (dimmed),
     * and description aligned in columns.
     *
     * @param Style $style Instance of Style to apply formatting
     * @return Style Returns the modified Style instance for chaining
     */
    public function printCommands(Style $style): Style
    {
        $optionNames = array_keys($this->commandDescribes);
        $minLength = $this->printHelp['column-1-min-length'];

        foreach ($optionNames as $name) {
            $argumentsLength = 0;
            if (isset($this->commandRelation[$name])) {
                $arguments       = implode(' ', $this->commandRelation[$name]);
                $argumentsLength = strlen($arguments);
            }

            $length = strlen($name) + $argumentsLength;
            if ($length > $minLength) {
                $minLength = $length;
            }
        }

        foreach ($this->commandDescribes as $option => $describe) {
            $arguments = '';
            if (isset($this->commandRelation[$option])) {
                $arguments = implode(' ', $this->commandRelation[$option]);
                $arguments = ' ' . $arguments;
            }

            $style->repeat(' ', $this->printHelp['margin-left']);
            $style->push($option)->textGreen();
            $style->push($arguments)->textDim();

            $range = $minLength - (strlen($option) + strlen($arguments));
            $style->repeat(' ', $range + 8);

            $style->push($describe);
            $style->newLines();
        }

        return $style;
    }

    /**
     * Render and style option descriptions in console output.
     *
     * Each option is printed with a padded name (dimmed) and
     * aligned description in the second column.
     *
     * @param Style $style Instance of Style to apply formatting
     * @return Style Returns the modified Style instance for chaining
     */
    public function printOptions(Style $style): Style
    {
        $optionNames = array_keys($this->optionDescribes);
        $minLength = $this->printHelp['column-1-min-length'];

        foreach ($optionNames as $name) {
            $length = strlen($name);
            if ($length > $minLength) {
                $minLength = $length;
            }
        }

        foreach ($this->optionDescribes as $option => $describe) {
            $style->repeat(' ', $this->printHelp['margin-left']);

            $optionName = Str::fillEnd($option, ' ', $minLength + 8);
            $style->push($optionName)->textDim();

            $style->push($describe);
            $style->newLines();
        }

        return $style;
    }
}
