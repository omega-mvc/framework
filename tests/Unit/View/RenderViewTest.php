<?php

declare(strict_types=1);

namespace Tests\View;

use Omega\View\Exceptions\ViewFileNotFoundException;
use Omega\View\Portal;
use Omega\View\View;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;
use Tests\TestCase;

use function ob_get_clean;
use function ob_start;
use function str_replace;

#[CoversClass(Portal::class)]
#[CoversClass(ViewFileNotFoundException::class)]
#[CoversClass(View::class)]
final class RenderViewTest extends TestCase
{
    public function testCanRenderUsingViewClasses(): void
    {
        $testHtml = __DIR__ . '/fixtures/view/sample/sample.html';
        $testPhp  = __DIR__ . '/fixtures/view/sample/sample.php';

        ob_start();
        View::render($testHtml)->send();
        $renderHtml = ob_get_clean();

        ob_start();
        View::render($testPhp, ['contents' => ['say' => 'hay']])->send();
        $renderPhp = ob_get_clean();

        $this->assertEquals(
            "<html><head></head><body></body></html>\n",
            str_replace("\r\n", "\n", (string) $renderHtml)
        );

        $this->assertEquals(
            "<html><head></head><body><h1>hay</h1></body></html>\n",
            str_replace("\r\n", "\n", (string) $renderPhp)
        );
    }

    public function testThrowWhenFileNotFound(): void
    {
        $this->expectException(ViewFileNotFoundException::class);
        View::render('unknown');
    }

    public function testPortalHasMethod(): void
    {
        $data = [
            'auth'     => ['user' => 'admin'],
            'meta'     => ['title' => 'Home'],
            'contents' => ['say' => 'hello'],
        ];

        $viewPath = __DIR__ . '/fixtures/view/sample/sample.php';

        $response = View::render($viewPath, $data);

        $reflection = new ReflectionClass(View::class);
        $authProp   = $reflection->getMethod('render')->getStaticVariables()['auth'] ?? null;

        $authPortal    = new Portal($data['auth']);
        $metaPortal    = new Portal($data['meta']);
        $contentPortal = new Portal($data['contents']);

        $this->assertTrue($authPortal->has('user'));
        $this->assertFalse($authPortal->has('unknown'));

        $this->assertTrue($metaPortal->has('title'));
        $this->assertFalse($metaPortal->has('unknown'));

        $this->assertTrue($contentPortal->has('say'));
        $this->assertFalse($contentPortal->has('other'));
    }
}
