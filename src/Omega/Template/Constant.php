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

use function count;
use function str_repeat;
use function str_replace;

/**
 * Represents a single class constant definition used in code generation.
 *
 * This component provides full control over visibility, value assignment,
 * inline comments, and formatting rules when generating a constant block
 * inside a class structure. It uses both FormatterTrait and CommentTrait
 * to support indentation control and automatic docblock generation.
 *
 * @category  Omega
 * @package   Template
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Constant
{
    use FormatterTrait;
    use CommentTrait;

    /** @var int Visibility flag: public constant. */
    public const int PUBLIC_ = 0;

    /** @var int Visibility flag: private constant. */
    public const int PRIVATE_ = 1;

    /** @var int Visibility flag: protected constant. */
    public const int PROTECTED_ = 2;

    /**
     * The visibility level of the generated constant.
     *
     * Defaults to -1, meaning no visibility is applied unless explicitly set.
     *
     * @var int
     */
    private int $visibility;

    /** @var string|null The name of the constant being generated. */
    private ?string $name;

    /**
     * The value to assign to the constant.
     *
     * If null, the generator defaults to `= null`. Otherwise, this holds the
     * raw assignment string (e.g., `"= 'value'"`, `"= 123"`).
     *
     * @var string|null
     */
    private ?string $expecting = null;

    /**
     * Creates a new constant definition.
     *
     * @param string $name The constant name.
     * @return void
     */
    public function __construct(string $name)
    {
        $this->name       = $name;
        $this->visibility = -1;
    }

    /**
     * Converts the constant to its generated string representation.
     *
     * @return string The fully generated constant definition.
     */
    public function __toString(): string
    {
        return $this->generate();
    }

    /**
     * Creates a new Constant instance using fluent syntax.
     *
     * @param string $name The constant name.
     * @return self A new Constant instance.
     */
    public static function new(string $name): self
    {
        return new self($name);
    }

    /**
     * Returns the template used to generate the constant.
     *
     * If `customizeTemplate()` has been called, the user-defined template
     * is used; otherwise, the default constant template is returned.
     *
     * @return string The constant template structure.
     */
    private function planTemplate(): string
    {
        return $this->customizeTemplate ?? '{{comment}}{{visibility}}const {{name}}{{expecting}};';
    }

    /**
     * Generates the final constant string, including comments,
     * visibility, name, and assigned value.
     *
     * @return string The formatted constant block.
     */
    public function generate(): string
    {
        $template = $this->planTemplate();
        $tabDept  = fn (int $dept) => str_repeat($this->tabIndent, $dept * $this->tabSize);

        $comment = $this->generateComment(1, $this->tabIndent);
        /** @noinspection DuplicatedCode */
        $comment = count($this->comments) > 0
            ? $comment . "\n" . $tabDept(1)
            : $comment;

        // visibility
        $visibility = '';
        switch ($this->visibility) {
            case self::PUBLIC_:
                $visibility = 'public ';
                break;
            case self::PROTECTED_:
                $visibility = 'protected ';
                break;
            case self::PRIVATE_:
                $visibility = 'private ';
                break;
        }

        // expecting / value
        $expecting = $this->expecting === null
            ? ' = null'
            : ' ' . $this->expecting;

        return str_replace(
            ['{{comment}}', '{{visibility}}', '{{name}}', '{{expecting}}'],
            [$comment, $visibility, $this->name, $expecting],
            $template
        );
    }

    /**
     * Sets the constant visibility.
     *
     * @param int $visibility One of Constant::PUBLIC_, PRIVATE_, PROTECTED_.
     * @return self For fluent method chaining.
     */
    public function visibility(int $visibility = self::PUBLIC_): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Renames the constant.
     *
     * @param string $name The new constant name.
     * @return self For fluent method chaining.
     */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the raw assignment expression of the constant.
     *
     * Example: `"= 'test'"`, `"= 123"`.
     *
     * @param string $expecting The raw assignment part.
     * @return self For fluent method chaining.
     */
    public function expecting(string $expecting): self
    {
        $this->expecting = $expecting;

        return $this;
    }

    /**
     * Sets the constant value with automatic "= value" prefix.
     *
     * Example: passing `"123"` results in `"= 123"`.
     *
     * @param string $expectingWith The right-hand side of the assignment.
     * @return self For fluent method chaining.
     */
    public function equal(string $expectingWith): self
    {
        $this->expecting = '= ' . $expectingWith;

        return $this;
    }
}
