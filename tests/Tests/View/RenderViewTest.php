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
use Omega\View\Portal;
use Omega\View\View;
use ReflectionClass;
use Tests\FixturesPathTrait;

use function ob_get_clean;
use function ob_start;
use function str_replace;

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
#[CoversClass(Portal::class)]
#[CoversClass(ViewFileNotFoundException::class)]
#[CoversClass(View::class)]
final class RenderViewTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Test it can render using view classes.
     *
     * @return void
     */
    public function testItCanRenderUsingViewClasses(): void
    {
        $testHtml  = $this->setFixturePath('/fixtures/view/sample/sample.html');
        $testPhp   = $this->setFixturePath('/fixtures/view/sample/sample.php');

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
        View::render('unknown');
    }

    /**
     * Test the has() method of Portal via View rendering.
     *
     * @return void
     */
    public function testPortalHasMethod(): void
    {
        $data = [
            'auth' => ['user' => 'admin'],
            'meta' => ['title' => 'Home'],
            'contents' => ['say' => 'hello'],
        ];

        $viewPath = $this->setFixturePath('/fixtures/view/sample/sample.php');

        // Otteniamo il Response
        $response = View::render($viewPath, $data);

        // Recuperiamo i Portal interni usando reflection
        $reflection = new ReflectionClass(View::class);
        $authProp = $reflection->getMethod('render')->getStaticVariables()['auth'] ?? null;

        // In alternativa, possiamo testare direttamente Portal
        $authPortal = new Portal($data['auth']);
        $metaPortal = new Portal($data['meta']);
        $contentPortal = new Portal($data['contents']);

        // Test has() su Portal
        $this->assertTrue($authPortal->has('user'));
        $this->assertFalse($authPortal->has('unknown'));

        $this->assertTrue($metaPortal->has('title'));
        $this->assertFalse($metaPortal->has('unknown'));

        $this->assertTrue($contentPortal->has('say'));
        $this->assertFalse($contentPortal->has('other'));
    }
}
