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
use Omega\View\Templator;
use Omega\View\TemplatorFinder;

/**
 * Test suite for the SetTemplator.
 *
 * Ensures that `{% set %}` directives correctly assign values
 * to variables inside templates, supporting different data types.
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
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
final class SetTest extends TestCase
{
    /**
     * Test it can render set string.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderSetString(): void
    {
        $templator = new Templator(new TemplatorFinder([__DIR__], ['']), __DIR__);
        $out       = $templator->templates('{% set $foo=\'bar\' %}');
        $this->assertEquals('<?php $foo = \'bar\'; ?>', $out);
    }


    /**
     * Test it can render set int.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderSetInt(): void
    {
        $templator = new Templator(new TemplatorFinder([__DIR__], ['']), __DIR__);
        $out       = $templator->templates('{% set $bar=123 %}');
        $this->assertEquals('<?php $bar = 123; ?>', $out);
    }

    /**
     * Test it can render set array.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderSetArray(): void
    {
        $templator = new Templator(new TemplatorFinder([__DIR__], ['']), __DIR__);
        $out       = $templator->templates('{% set $arr=[12, \'34\'] %}');
        $this->assertEquals('<?php $arr = [12, \'34\']; ?>', $out);
    }
}
