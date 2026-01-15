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

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Text\Str;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;

/**
 * Test suite for the UseTemplator.
 *
 * Ensures that `{% use %}` directives correctly generate PHP `use`
 * statements and support multiple and aliased imports.
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
#[CoversClass(Str::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
final class UseTest extends TestCase
{
    /**
     * Test it can render use.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderUse(): void
    {
        $templator = new Templator(new TemplatorFinder([__DIR__ . '/view/'], ['']), __DIR__);
        $out       = $templator->templates("'<html>{% use ('Test\Test') %}</html>");
        $match     = Str::contains($out, 'use Test\Test');
        $this->assertTrue($match);
    }

    /**
     * Test it can render multi time.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderUseMultiTime(): void
    {
        $templator = new Templator(new TemplatorFinder([__DIR__ . '/view/'], ['']), __DIR__);
        $out       = $templator->templates(
            "'<html>{% use ('Test\Test') %}{% use ('Test\Test as Test2') %}</html>"
        );
        $match     = Str::contains($out, 'use Test\Test');
        $this->assertTrue($match);
        $match     = Str::contains($out, 'use Test\Test as Test2');
        $this->assertTrue($match);
    }
}
