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

namespace Omega\Console;

use Exception;
use Omega\Console\Style\Style;

use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_pop;
use function array_values;
use function chr;
use function fgets;
use function fwrite;
use function join;
use function ord;
use function readline_callback_handler_install;
use function stream_get_contents;
use function trim;

use const STDIN;
use const STDOUT;

/**
 * Class Prompt
 *
 * Provides interactive console input handling, allowing commands to present
 * questions, selection menus, password prompts, and generic text inputs to the user.
 *
 * The Prompt class integrates with the Style class for output formatting and
 * supports different types of input:
 * - Direct option selection by key
 * - Sequential numbered selection
 * - Free text input
 * - Password input with optional masking
 * - "Press any key" behavior
 *
 * This class is designed to simplify user interaction in CLI applications.
 *
 * @category  Omega
 * @package   Console
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Prompt
{
    /** @var string|Style Title or message shown before the prompt. */
    private string|Style $title;

    /**
     * A map of input keys to callback functions.
     * The key represents the user input, and the value is a callable executed when matched.
     *
     * @var array<string, callable>
     */
    private array $options;

    /** @var string Default option key to use when user input does not match any defined option. */
    private string $default;

    /**
     * List of selectable options displayed to the user.
     * Each entry may be a plain string or a Style object for advanced formatting.
     *
     * @var string[]|Style[]
     */
    private array $selection;

    /**
     * Constructor.
     *
     * @param string|Style $title   Title or question displayed before the prompt.
     * @param array<string, callable> $options  List of input-to-callback mappings.
     * @param string $default       Default option key to fall back to if input does not match.
     */
    public function __construct(Style|string $title, array $options = [], string $default = '')
    {
        $this->title     = $title;
        $this->options   = array_merge(['' => fn () => false], $options);
        $this->default   = $default;
        $this->selection = array_keys($options);
    }

    /**
     * Reads a single line of input from STDIN.
     *
     * @return string User input without trailing newlines or spaces.
     * @throws Exception If STDIN cannot be read.
     */
    private function getInput(): string
    {
        $input = fgets(STDIN);

        if ($input === false) {
            throw new Exception('Cant read input');
        }

        return trim($input);
    }

    /**
     * Sets the list of options available for selection display.
     *
     * @param string[]|Style[] $selection Options to display for user choice.
     * @return self
     */
    public function selection(array $selection): self
    {
        $this->selection = $selection;

        return $this;
    }

    /**
     * Displays the prompt and allows the user to choose one of the predefined options.
     * If the input matches a key in $options, the corresponding callback is executed.
     * If not, the default option is executed.
     *
     * @return mixed Result of the executed callback.
     * @throws Exception If reading from STDIN fails.
     */
    public function option(): mixed
    {
        $style = new Style();
        $style->push((string)$this->title)->push(' ');
        foreach ($this->selection as $option) {
            if ($option instanceof Style) {
                $style->tap($option);
            } else {
                $style->push("$option ");
            }
        }

        $style->out();
        $input = $this->getInput();
        if (array_key_exists($input, $this->options)) {
            return ($this->options[$input])();
        }

        return ($this->options[$this->default])();
    }

    /**
     * Displays the prompt as a numbered list of selectable options.
     * The user must input the index of the desired option.
     *
     * @return mixed Result of the executed callback.
     * @throws Exception If reading from STDIN fails.
     */
    public function select(): mixed
    {
        $style = new Style();
        $style->push($this->title);
        $i = 1;
        foreach ($this->selection as $option) {
            if ($option instanceof Style) {
                $style->tap($option);
            } else {
                /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
                $style->newLines()->push("[{$i}] {$option}");
            }
            $i++;
        }

        $style->out();
        $input  = $this->getInput();
        $select = array_values($this->options);

        if (array_key_exists($input, $select)) {
            return ($select[$input])();
        }

        return ($this->options[$this->default])();
    }

    /**
     * Prompts the user for text input and passes it to the provided callback.
     *
     * @param callable $callable Callback executed with the user input string.
     * @return mixed Result of the callback.
     * @throws Exception If reading from STDIN fails.
     */
    public function text(callable $callable): mixed
    {
        new Style($this->title)->out();

        return ($callable)($this->getInput());
    }

    /**
     * Prompts the user for password input, hiding characters as they are typed.
     * An optional mask character (e.g., "*") can be displayed instead of typed characters.
     *
     * @param callable $callable Callback executed with the entered password.
     * @param string $mask Optional mask character shown instead of actual characters.
     * @return mixed Result of the callback.
     */
    public function password(callable $callable, string $mask = ''): mixed
    {
        new Style($this->title)->out();

        $userLine = [];
        readline_callback_handler_install('', function () {
        });
        while (true) {
            $keystroke = stream_get_contents(STDIN, 1);

            switch (ord($keystroke)) {
                case 10:
                    break 2;

                case 127:
                    array_pop($userLine);
                    fwrite(STDOUT, chr(8));
                    fwrite(STDOUT, "\033[0K");
                    break;

                default:
                    $userLine[] = $keystroke;
                    fwrite(STDOUT, $mask);
                    break;
            }
        }

        return ($callable)(join($userLine));
    }

    /**
     * Waits for the user to press any key, then executes the provided callback.
     *
     * @param callable $callable Callback executed with the pressed key character.
     * @return mixed Result of the callback.
     */
    public function anyKey(callable $callable): mixed
    {
        $prompt = (string) $this->title;
        readline_callback_handler_install($prompt, function () {
        });
        $keystroke = stream_get_contents(STDIN, 1);

        return ($callable)($keystroke);
    }
}
