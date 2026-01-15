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
use function count;
use function implode;
use function is_array;
use function str_repeat;
use function str_replace;

use const ARRAY_FILTER_USE_KEY;

/**
 * Represents a programmable PHP class property.
 *
 * This class provides a fluent API to configure a property’s visibility,
 * static modifier, type, name, comments, and default or expected values.
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
class Property
{
    use FormatterTrait;
    use CommentTrait;

    /** @var bool Whether the property is marked as static. */
    private bool $isStatic = false;

    /** @var int Visibility of the property. */
    private int $visibility = self::PRIVATE_;

    /** @var int Public visibility constant. */
    public const int PUBLIC_ = 0;

    /** @var int Private visibility constant. */
    public const int PRIVATE_ = 1;

    /** @var int Protected visibility constant. */
    public const int PROTECTED_ = 2;

    /** @var string Data type of the property (e.g., string, int, array). */
    private string $dataType;

    /** @var string Property name (without `$` sign). */
    private string $name;

    /** @var string[]|null Optional default value(s) or expected assignment. Can be single-line or multi-line. */
    private ?array $expecting = null;

    /**
     * Constructor.
     *
     * @param string $name The property name.
     * @return void
     */
    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * Generates the property source code when cast to string.
     *
     * @return string The generated property code.
     */
    public function __toString(): string
    {
        return $this->generate();
    }

    /**
     * Factory helper to create a new Property instance.
     *
     * @param string $name The property name.
     * @return self A new Property instance.
     */
    public static function new(string $name): self
    {
        return new self($name);
    }

    /**
     * Returns the active template used to generate the property.
     *
     * If no custom template is defined, a default property structure is used.
     *
     * @return string The resolved template string.
     */
    private function planTemplate(): string
    {
        return $this->customizeTemplate ?? '{{comment}}{{visibility}}{{static}}{{data type}}{{name}}{{expecting}};';
    }

    /**
     * Generates the final PHP code for the property.
     *
     * @return string The fully rendered property code.
     */
    public function generate(): string
    {
        $template = $this->planTemplate();
        $tabDept  = fn (int $dept) => str_repeat($this->tabIndent, $dept * $this->tabSize);

        $comment = $this->generateComment(1);
        /** @noinspection DuplicatedCode */
        $comment = count($this->comments) > 0
        ? $comment . "\n" . $tabDept(1)
        : $comment;

        // generate visibility
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

        // generate static
        $static = $this->isStatic ? 'static ' : '';

        // data type
        $data_type = $this->dataType ?? '';

        // generate name
        $name = '$' . $this->name;

        // generate value or expecting
        $expecting = '';
        if ($this->expecting !== null) {
            $singleLine  = $this->expecting[0] ?? '';
            $multiLine   = implode(
                "\n" . $tabDept(1),
                array_filter($this->expecting, fn ($key) => $key > 0, ARRAY_FILTER_USE_KEY)
            );
            $expecting = count($this->expecting) > 1
            ? ' ' . $singleLine . "\n" . $tabDept(1) . $multiLine
            : ' ' . $singleLine;
        }

        // final
        return str_replace(
            ['{{comment}}', '{{visibility}}', '{{static}}', '{{data type}}', '{{name}}', '{{expecting}}'],
            [$comment, $visibility, $static, $data_type, $name, $expecting],
            $template
        );
    }

    /**
     * Sets whether the property is static.
     *
     * @param bool $isStatic True to mark the property as static, false otherwise.
     * @return self Returns the current instance for chaining.
     */
    public function setStatic(bool $isStatic = true): self
    {
        $this->isStatic = $isStatic;

        return $this;
    }

    /**
     * Sets the property visibility.
     *
     * @param int $visibility One of the Property::* visibility constants.
ù     * @return self Returns the current instance for chaining.
     */
    public function visibility(int $visibility = self::PUBLIC_): self
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * Sets the property data type.
     *
     * @param string $dataType The property type (e.g., "string", "int").
     *
     * @return self Returns the current instance for chaining.
     */
    public function dataType(string $dataType): self
    {
        $this->dataType = $dataType . ' ';

        return $this;
    }

    /**
     * Sets the property name.
     *
     * @param string $name The property name (without `$` sign).
     * @return self Returns the current instance for chaining.
     */
    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the expected value(s) for the property.
     *
     * @param string|string[] $expecting A single value or array of lines for multi-line assignment.
     * @return self Returns the current instance for chaining.
     */
    public function expecting(array|string $expecting): self
    {
        $this->expecting = is_array($expecting) ? $expecting : [$expecting];

        return $this;
    }
}
