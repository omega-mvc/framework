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

namespace Tests\View;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\View\Exceptions\ViewFileNotFoundException;
use Omega\View\View;

use function ob_get_clean;
use function ob_start;
use function str_replace;

use const DIRECTORY_SEPARATOR;

/**
 * Test suite for the View renderer.
 *
 * Verifies that view files (HTML and PHP) are rendered correctly using
 * the View class and that missing view files trigger the expected exception.
 *
 * @category  Tests
 * @package   View
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(ViewFileNotFoundException::class)]
#[CoversClass(View::class)]
class RenderViewTest extends AbstractViewPath
{
    /**
     * Test it can render using view classes.
     *
     * @return void
     */
    public function testItCanRenderUsingViewClasses(): void
    {
        $testHtml  = $this->viewPath('sample/sample.html');
        $testPhp   = $this->viewPath('sample/sample.php' );

        ob_start();
        View::render($testHtml)->send();
        $renderHtml = ob_get_clean();

        ob_start();
        View::render($testPhp, ['contents' => ['say' => 'hay']])->send();
        $renderPhp = ob_get_clean();

        // view: view-html
        $this->assertEquals(
            "<html><head></head><body></body></html>\n",
            str_replace("\r\n", "\n", $renderHtml),
            'it must same output with template html'
        );

        // view: view-php
        $this->assertEquals(
            "<html><head></head><body><h1>hay</h1></body></html>\n",
            str_replace("\r\n", "\n", $renderPhp),
            'it must same output with template html'
        );
    }

    /**
     * Test it throw when file not found.
     *
     * @return void
     */
    public function testItThrowWhenFileNotFound(): void
    {
        $this->expectException(ViewFileNotFoundException::class);
        View::render('unknow');
    }
}
