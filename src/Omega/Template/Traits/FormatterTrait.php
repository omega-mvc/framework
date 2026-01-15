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

/** @noinspection PhpDocSignatureInspection */

declare(strict_types=1);

namespace Omega\Template\Traits;

/**
 * Provides low-level formatting utilities used when generating code structures.
 *
 * This trait allows consumers to control indentation style, indentation size,
 * and to supply custom template strings that override the default formatting
 * behavior. It is primarily used by code-generation components to ensure
 * consistent and customizable output.
 *
 * @category   Omega
 * @package    Template
 * @subpackage Traits
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
trait FormatterTrait
{
    /**
     * The number of indentation units to apply when generating formatted output.
     *
     * This value represents how many indentation steps (tabs or spaces, depending
     * on `$tabIndent`) should be inserted when rendering nested structures.
     *
     * @var int
     */
    protected int $tabSize = 1;

    /**
     * The character sequence used for a single indentation unit.
     *
     * Defaults to a tab character (`"\t"`), but may be replaced with spaces or
     * any custom indentation string, depending on formatting requirements.
     *
     * @var string
     */
    protected string $tabIndent = "\t";

    /**
     * A user-defined template that overrides the default formatting behavior.
     *
     * When provided, this template is used by code-generation components to
     * dictate the structure of the output. Placeholders within the template
     * are resolved by the implementing class.
     *
     * @var string
     */
    private string $customizeTemplate;

    /**
     * Sets the number of indentation steps applied when generating formatted code.
     *
     * @param int $tabSize The number of indentation units (tabs or spaces)
     *                     applied per indentation level.
     *
     * @return self Returns the current instance for fluent method chaining.
     */
    public function tabSize(int $tabSize): self
    {
        $this->tabSize = $tabSize;

        return $this;
    }

    /**
     * Sets the character sequence used for a single indentation unit.
     *
     * This method allows customization of indentation style, such as switching
     * from tabs to spaces or applying custom indentation tokens.
     *
     * @param string $tabIndent The indentation unit (e.g. "\t", "  ").
     *
     * @return self Returns the current instance for fluent method chaining.
     */
    public function tabIndent(string $tabIndent): self
    {
        $this->tabIndent = $tabIndent;

        return $this;
    }

    /**
     * Applies a custom template string to override the default code structure.
     *
     * The template may include placeholders recognized by the implementing
     * generator (such as "{{body}}", "{{comment}}", etc.), allowing fine-grained
     * control over how the output code is structured.
     *
     * @param string $template A custom output template.
     *
     * @return self Returns the current instance for fluent method chaining.
     */
    public function customizeTemplate(string $template): self
    {
        $this->customizeTemplate = $template;

        return $this;
    }
}
