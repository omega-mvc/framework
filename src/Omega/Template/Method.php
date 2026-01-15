<?php

/**
 * Part of Omega - Template Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Template;

use Omega\Template\Traits\CommentTrait;
use Omega\Template\Traits\FormatterTrait;

use function array_filter;
use function array_map;
use function count;
use function implode;
use function is_array;
use function str_repeat;

/**
 * Represents a programmable PHP method definition.
 *
 * This class provides a fluent API to configure every structural aspect
 * of a method, including its name, visibility, modifiers (final/static),
 * parameters, return type, code body, and associated comments.
 * It integrates formatting and comment-handling logic through
 * FormatterTrait and CommentTrait.
 *
 * @category  Omega
 * @package   Template
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Method
{
    use FormatterTrait;
    use CommentTrait;

    /** @var int Public visibility flag. */
    public const int PUBLIC_ = 0;

    /** @var int Private visibility flag. */
    public const int PRIVATE_ = 1;

    /** @var int Protected visibility flag. */
    public const int PROTECTED_ = 2;

    /** @var int Current visibility setting for the method. */
    private int $visibility = -1;

    /** @var bool Whether the method is marked as final. */
    private bool $isFinal = false;

    /** @var bool Whether the method is marked as static. */
    private bool $isStatic = false;

    /** @var string The method name. */
    private string $name;

    /** @var string[] List of parameter declarations. */
    private array $params = [];

    /** @var string|null The declared return type of the method, or null when unspecified. */
    private ?string $returnType = null;

    /** @var string[] List of code lines forming the method body. */
    private array $body = [];

    /**
     * Creates a new Method instance.
     *
     * @param string $name The method name.
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Generates the method source code when cast to string.
     *
     * @return string The generated method code.
     */
    public function __toString(): string
    {
        return $this->generate();
    }

    /**
     * Factory helper to create a new method instance.
     *
     * @param string $name The method name.
     * @return self A new Method instance.
     */
    public static function new(string $name): self
    {
        return new self($name);
    }

    /**
     * Returns the active template used to generate the method.
     *
     * If no custom template is defined, a default method structure is used.
     *
     * @return string The resolved template string.
     */
    public function planTemplate(): string
    {
        return $this->customizeTemplate
            ?? "{{comment}}{{before}}function {{name}}({{params}}){{return type}}{{new line}}{\n{{body}}{{new line}}}";
    }

    /**
     * Generates the final PHP code for the method.
     *
     * @return string The fully rendered method block.
     */
    public function generate(): string
    {
        $template = $this->planTemplate();
        $tabDept  = fn (int $dept) => str_repeat($this->tabIndent, $dept * $this->tabSize);
        // new line
        $newLine = "\n" . $tabDept(1);

        // comment
        $comment = $this->generateComment(1, $this->tabIndent);
        $comment = count($this->comments) > 0
            ? $comment . $newLine
            : $comment;

        $pre = [];
        // final
        $pre[] = $this->isFinal ? 'final' : '';

        // visibility
        $pre[] = match ($this->visibility) {
            self::PUBLIC_    => 'public',
            self::PRIVATE_   => 'private',
            self::PROTECTED_ => 'protected',
            default          => '',
        };

        // static
        $pre[] = $this->isStatic ? 'static' : '';

        // {{final}}{{visibility}}{{static}}
        $pre    = array_filter($pre);
        $before = implode(' ', $pre);
        $before .= count($pre) == 0 ? '' : ' ';

        // name
        $name = $this->name;

        // params
        $params = implode(', ', $this->params);

        // return type
        $return = isset($this->returnType) ? ': ' : '';
        $return .= $this->returnType;

        // body
        $bodies = array_map(fn ($x) => $tabDept(2) . $x, $this->body);
        $body   = implode("\n", $bodies);

        return str_replace(
            ['{{comment}}', '{{before}}', '{{name}}', '{{params}}', '{{new line}}', '{{body}}', '{{return type}}'],
            [$comment, $before, $name, $params, $newLine, $body, $return],
            $template
        );
    }

    /**
     * Sets the method name.
     *
     * @param string $name The new method name.
     * @return self Returns the current instance for chaining.
     */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the method visibility.
     *
     * @param int $visibility One of the Method::* visibility constants.
     * @return self Returns the current instance for chaining.
     */
    public function visibility(int $visibility = self::PUBLIC_): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Enables or disables the `final` modifier.
     *
     * @param bool $isFinal Whether the method should be final.
     * @return self Returns the current instance for chaining.
     */
    public function setFinal(bool $isFinal = true): self
    {
        $this->isFinal = $isFinal;

        return $this;
    }

    /**
     * Enables or disables the `static` modifier.
     *
     * @param bool $isStatic Whether the method should be static.
     * @return self Returns the current instance for chaining.
     */
    public function setStatic(bool $isStatic = true): self
    {
        $this->isStatic = $isStatic;

        return $this;
    }

    /**
     * Sets the full parameter list for the method.
     *
     * @param string[]|null $params A list of parameter definitions, or null to reset.
     * @return self Returns the current instance for chaining.
     */
    public function params(?array $params): self
    {
        $this->params = $params ?? [];

        return $this;
    }

    /**
     * Adds a single parameter to the list.
     *
     * @param string $param The parameter declaration string.
     * @return self Returns the current instance for chaining.
     */
    public function addParams(string $param): self
    {
        $this->params[] = $param;

        return $this;
    }

    /**
     * Sets or clears the method return type.
     *
     * @param string|null $returnType The return type, or null to unset.
     * @return self Returns the current instance for chaining.
     */
    public function setReturnType(?string $returnType): self
    {
        $this->returnType = $returnType ?? '';

        return $this;
    }

    /**
     * Sets the method body.
     *
     * @param string|string[]|null $body A raw string, an array of lines,
     *                                   or null to clear the body.
     * @return self Returns the current instance for chaining.
     */
    public function body(array|string|null $body): self
    {
        $body ??= [];

        $this->body = is_array($body) ? $body : [$body];

        return $this;
    }
}
