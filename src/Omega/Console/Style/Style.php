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

namespace Omega\Console\Style;

use Omega\Console\IO\OutputStreamInterface;
use Omega\Console\Style\Color\BackgroundColor;
use Omega\Console\Style\Color\ForegroundColor;
use Omega\Console\Style\Color\RuleInterface;
use Omega\Console\Traits\CommandTrait;
use Omega\Text\Str;

use function max;
use function method_exists;
use function Omega\Text\text;
use function preg_replace;
use function str_repeat;
use function strlen;
use function strtolower;

use const PHP_EOL;
use const STR_PAD_RIGHT;

/**
 * Class Style
 *
 * Provides a fluent interface for styling terminal text with colors,
 * background colors, and text decorations (bold, underline, blink, etc.).
 * Supports chaining, conditional printing, raw codes, and output streams.
 *
 * Dynamic method calls (`__call`) allow you to apply colors or backgrounds by name,
 * for example: `$style->textRed()->bgBlue()->bold()`.
 *
 * Dynamic invocation (`__invoke`) allows setting the text to style: `$style('Hello World')`.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Style
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @method self textRed()
 * @method self textYellow()
 * @method self textBlue()
 * @method self textGreen()
 * @method self textDim()
 * @method self textMagenta()
 * @method self textCyan()
 * @method self textLightGray()
 * @method self textDarkGray()
 * @method self textLightGreen()
 * @method self textLightYellow()
 * @method self textLightBlue()
 * @method self textLightMagenta()
 * @method self textLightCyan()
 * @method self textWhite()
 * @method self bgRed()
 * @method self bgYellow()
 * @method self bgBlue()
 * @method self bgGreen()
 * @method self bgMagenta()
 * @method self bgCyan()
 * @method self bgLightGray()
 * @method self bgDarkGray()
 * @method self bgLightGreen()
 * @method self bgLightYellow()
 * @method self bgLightBlue()
 * @method self bgLightMagenta()
 * @method self bgLightCyan()
 * @method self bgWhite()
 * @method text_red_500()
 * @method bg_blue_500()
 * @method text_red_10()
 */
class Style
{
    use CommandTrait;

    /** @var array<int, int> Array of terminal style rules.
     * @noinspection PhpPrivateFieldCanBeLocalVariableInspection
     */
    private array $rules = [];

    /** @var array<int, array<int, string|int>> Raw terminal codes. */
    private array $rawRules = [];

    /** @var array<int, int> Reset rules applied after printing. */
    private array $resetRules = [Decorate::RESET];

    /** @var array<int, int> Foreground color rules. */
    private array $textColorRule = [Decorate::TEXT_DEFAULT];

    /** @var array<int, int> Background color rules. */
    private array $bgColorRule = [Decorate::BG_DEFAULT];

    /** @var array<int, int> Text decoration rules (bold, underline, etc.). */
    private array $decorateRules = [];

    /** @var int Length of the current text (without ANSI codes). */
    private int $length;

    /** @var string Prefix or reference text appended before main text. */
    private string $ref = '';

    /** @var OutputStreamInterface|null Optional output stream for writing. */
    private ?OutputStreamInterface $outputStream = null;

    /** @var bool|mixed  */
    private bool $colorize;

    /** @var bool Indicate decorate is allowed. */
    private bool $decorate;

    /**
     * @param string|int $text Text to decorate
     * @param array{
     *  colorize?: bool,
     *  decorate?: bool
     * }                 $options
     *                            Options for style:
     *                            - colorize: bool, default true, if false will not colorize text
     *                            - decorate: bool, default true, if false will not decorate text
     */
    public function __construct(
        private string|int $text = '',
        array $options = [],
    ) {
        $this->length   = strlen((string) $text);
        $this->colorize = $options['colorize'] ?? true;
        $this->decorate = $options['decorate'] ?? true;
    }

    /**
     * Invoke the Style instance to set the text dynamically.
     *
     * @param string|int $text Text to style
     * @return self
     */
    public function __invoke(string|int $text): self
    {
        $this->text   = $text;
        $this->length = strlen((string) $text);

        return $this->flush();
    }

    /**
     * Return the fully styled text as string with ANSI codes.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString($this->text, $this->ref);
    }

    /**
     * Dynamic method call to apply colors or backgrounds by method name.
     *
     * @param string $name Method name
     * @param array<int, mixed> $arguments Optional arguments (ignored)
     * @return self
     */
    public function __call(string $name, array $arguments): self
    {
        if (method_exists($this, $name)) {
            $constant = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name));

            if (Str::startsWith($name, 'text')) {
                $constant              = 'TEXT' . text($constant)->upper()->slice(4);
                $this->textColorRule = [Decorate::getConstant($constant)];
            }

            if (Str::startsWith($name, 'bg')) {
                $constant            =  'BG' . text($constant)->upper()->slice(2);
                $this->bgColorRule = [Decorate::getConstant($constant)];
            }

            return $this;
        }

        $constant = text($name)->upper();
        if ($constant->startsWith('TEXT_')) {
            $constant->slice(5);
            $this->textColor(Colors::hexText(ColorVariant::getConstant($constant->__toString())));
        }

        if ($constant->startsWith('BG_')) {
            $constant->slice(3);
            $this->bgColor(Colors::hexBg(ColorVariant::getConstant($constant->__toString())));
        }

        return $this;
    }

    /**
     * Return the fully composed text with all rules applied.
     *
     * @param string|int $text Text to style
     * @param string $ref Optional prefix reference
     * @return string
     */
    public function toString(string|int $text, string $ref = ''): string
    {
        // make sure not push empty text
        if ($text == '' && $ref == '') {
            return '';
        }

        // flush
        $this->rules = [];

        if ($this->colorize) {
            // font color
            foreach ($this->textColorRule as $textColor) {
                $this->rules[] = $textColor;
            }

            // bg color
            foreach ($this->bgColorRule as $bgColor) {
                $this->rules[] = $bgColor;
            }
        }

        if ($this->decorate) {
            // decorate
            foreach ($this->decorateRules as $decorate) {
                $this->rules[] = $decorate;
            }

            // raw
            foreach ($this->rawRules as $raws) {
                foreach ($raws as $raw) {
                    $this->rules[] = $raw;
                }
            }
        }

        $resetRule = false === $this->decorate && false === $this->colorize
            ? []
            : $this->resetRules;

        return $ref . $this->rules(
            rule: $this->rules,
            text: $text,
            resetRule: $resetRule
        );
    }

    /**
     * Reset all rules and flush state.
     *
     * @return self
     */
    public function flush(): self
    {
        $this->textColorRule = [Decorate::TEXT_DEFAULT];
        $this->bgColorRule   = [Decorate::BG_DEFAULT];
        $this->decorateRules = [];
        $this->resetRules    = [Decorate::RESET];
        $this->rawRules      = [];
        $this->ref           = '';

        return $this;
    }

    /**
     * Set reference (add before main text).
     *
     * @param string $textReference
     * @return self
     */
    private function ref(string $textReference): self
    {
        $this->ref = $textReference;

        return $this;
    }

    /**
     * Append text for chaining styles.
     *
     * @param string|int $text Text to append
     * @return self
     */
    public function push(string|int $text): self
    {
        $ref        = $this->toString($this->text, $this->ref);
        $this->text = $text;

        $this->length += strlen((string)$text);

        return $this->flush()->ref($ref);
    }

    /**
     * Append another Style instance.
     *
     * @param Style $style Style instance to append
     * @return self
     */
    public function tap(Style $style): self
    {
        $this->ref           = $this->toString($this->text, $this->ref) . $style->toString($style->ref);
        $this->text          = $style->text;
        $this->textColorRule = $style->textColorRule;
        $this->bgColorRule   = $style->bgColorRule;
        $this->decorateRules = $style->decorateRules;
        $this->resetRules    = $style->resetRules;
        $this->rawRules      = $style->rawRules;

        $this->length += $style->length;

        return $this;
    }

    /**
     * Get the length of text (without ANSI codes).
     *
     * @return int
     */
    public function length(): int
    {
        return $this->length;
    }

    /**
     * Print the styled text to terminal.
     *
     * @param bool $newLine Append newline if true
     * @return void
     */
    public function out(bool $newLine = true): void
    {
        $out = $this . ($newLine ? PHP_EOL : null);

        echo $out;
    }

    /**
     * Print styled text conditionally.
     *
     * @param bool $condition Print if true
     * @param bool $newLine Append newline if true
     * @return void
     */
    public function outIf(bool $condition, bool $newLine = true): void
    {
        if ($condition) {
            $out = $this . ($newLine ? PHP_EOL : null);

            echo $out;
        }
    }

    /**
     * Print text and clear internal buffer.
     *
     * @return self
     */
    public function yield(): self
    {
        echo $this;
        $this->text   = '';
        $this->length = 0;
        $this->flush();

        return $this;
    }

    /**
     * Write to optional output stream.
     *
     * @param bool $newLine Append newline if true
     * @return void
     */
    public function write(bool $newLine = true): void
    {
        $out = $this . ($newLine ? PHP_EOL : null);

        $this->outputStream?->write($out);
    }

    /**
     * Write stream out.
     *
     * @param bool $condition
     * @param bool $newLine
     * @return void
     */
    public function writeIf(bool $condition, bool $newLine = true): void
    {
        if ($this->outputStream && true === $condition) {
            $out = $this . ($newLine ? PHP_EOL : null);
            $this->outputStream->write($out);
        }
    }

    /**
     * Clear current terminal line.
     *
     * @param int $line Optional line offset
     * @return void
     */
    public function clear(int $line = -1): void
    {
        $this->clearLine($line);
    }

    /**
     * Replace current terminal line content.
     *
     * @param string $text Replacement text
     * @param int $line Optional line offset
     * @return void
     */
    public function replace(string $text, int $line = -1): void
    {
        $this->replaceLine($text, $line);
    }

    /**
     * Set an output stream.
     *
     * @param OutputStreamInterface $resourceOutputStream
     * @return self
     */
    public function setOutputStream(OutputStreamInterface $resourceOutputStream): self
    {
        $this->outputStream = $resourceOutputStream;

        return $this;
    }

    /**
     * Reset all attributes (set reset decorate to be 0).
     *
     * @return self
     */
    public function resetDecorate(): self
    {
        $this->resetRules = [Decorate::RESET];

        return $this;
    }

    /**
     * Apply bold decoration.
     *
     * @return self
     */
    public function bold(): self
    {
        $this->decorateRules[] = Decorate::BOLD;
        $this->resetRules[]    = Decorate::RESET_BOLD;

        return $this;
    }

    /**
     * Apply underline decoration.
     *
     * @return self
     */
    public function underline(): self
    {
        $this->decorateRules[] = Decorate::UNDERLINE;
        $this->resetRules[]    = Decorate::RESET_UNDERLINE;

        return $this;
    }

    /**
     * Apply blink decoration.
     *
     * @return self
     */
    public function blink(): self
    {
        $this->decorateRules[] = Decorate::BLINK;
        $this->resetRules[]    = Decorate::RESET_BLINK;

        return $this;
    }

    /**
     * Apply reverse (invert) decoration.
     *
     * @return self
     */
    public function reverse(): self
    {
        $this->decorateRules[] = Decorate::REVERSE;
        $this->decorateRules[] = Decorate::RESET_REVERSE;

        return $this;
    }

    /**
     * Apply hidden text decoration.
     *
     * @return self
     */
    public function hidden(): self
    {
        $this->decorateRules[] = Decorate::HIDDEN;
        $this->resetRules[]    = Decorate::RESET_HIDDEN;

        return $this;
    }

    /**
     * Add raw terminal code or color.
     *
     * @param string|RuleInterface $raw Raw code or color instance
     * @return self
     */
    public function raw(string|RuleInterface $raw): self
    {
        if ($raw instanceof ForegroundColor) {
            $this->textColorRule = $raw->getRule();

            return $this;
        }

        if ($raw instanceof BackgroundColor) {
            $this->bgColorRule = $raw->getRule();

            return $this;
        }

        $this->rawRules[] = [$raw];

        return $this;
    }

    /**
     * Reset specific ANSI codes.
     *
     * @param int[] $reset Array of reset codes
     * @return self
     */
    public function rawReset(array $reset): self
    {
        $this->resetRules = $reset;

        return $this;
    }

    /**
     * Set foreground color.
     *
     * @param ForegroundColor|string $color
     * @return self
     */
    public function textColor(ForegroundColor|string $color): self
    {
        $this->textColorRule = $color instanceof ForegroundColor
            ? $color->getRule()
            : Colors::hexText($color)->getRule()
        ;

        return $this;
    }
    /**
     * Set background color.
     *
     * @param BackgroundColor|string $color
     * @return self
     */
    public function bgColor(BackgroundColor|string $color): self
    {
        $this->bgColorRule = $color instanceof BackgroundColor
            ? $color->getRule()
            : Colors::hexBg($color)->getRule();

        return $this;
    }

    /**
     * Repeat and push string content.
     *
     * @param string $content Content to repeat
     * @param int $repeat Number of times to repeat
     * @return self
     */
    public function repeat(string $content, int $repeat = 1): self
    {
        $repeat = max($repeat, 0);

        return $this->push(
            str_repeat($content, $repeat)
        );
    }

    /**
     * Append new lines.
     *
     * @param int $repeat Number of lines
     * @return self
     */
    public function newLines(int $repeat = 1): self
    {
        return $this->repeat("\n", $repeat);
    }

    /**
     * Append tabs.
     *
     * @param int $count Number of tabs
     * @return self
     */
    public function tabs(int $count = 1): self
    {
        return $this->repeat("\t", $count);
    }

    public function pad(string $text, int $length, string $padString = '', int $padType = STR_PAD_RIGHT): self
    {
        return $this->push(str_pad($text, $length, $padString, $padType));
    }
}
