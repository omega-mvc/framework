<?php

/**
 * Part of Omega - Tests\View Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Tests\View\Templator;

use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * A simple test component class used in ComponentTest.
 *
 * Provides a render method that outputs HTML using the background and size
 *
 * @category   Tests
 * @package    View
 * @subpackage Templator
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[CoversNothing]
class TestClassComponent
{
    /** @var string CSS class for background color. */
    private string $bg;

    /** @var string CSS class for size modifier. */
    private string $size;

    /**
     * Constructor.
     *
     * @param string $bg   Background CSS class.
     * @param string $size Size CSS class.
     */
    public function __construct(string $bg, string $size)
    {
        $this->bg   = $bg;
        $this->size = $size;
    }

    /**
     * Render the component with inner content.
     *
     * @param string $inner Inner HTML content of the component.
     * @return string Rendered HTML of the component.
     */
    public function render(string $inner): string
    {
        /** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
        return "<p class=\"{$this->bg} {$this->size}\">{$inner}</p>";
    }
}
