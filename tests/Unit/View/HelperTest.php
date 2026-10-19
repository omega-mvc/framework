<?php

declare(strict_types=1);

namespace Tests\View;

use Omega\Application\Application;
use Omega\Http\Response;
use Omega\Text\Str;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use Omega\View\View;
use Omega\View\Vite;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use Tests\TestCase;

use function Omega\View\view;
use function Omega\View\vite;

#[CoversClass(Application::class)]
#[CoversClass(View::class)]
#[CoversClass(Vite::class)]
final class HelperTest extends TestCase
{
    public function testViteHelperHandlesSingleAndMultipleEntryPoints(): void
    {
        $app = new Application(__DIR__);
        $viteMock = $this->createMock(Vite::class);
        $app->set('vite.gets', $viteMock);

        $viteMock->expects($this->exactly(2))
            ->method('gets')
            ->willReturnOnConsecutiveCalls(
                ['main.js' => 'url_string'],
                ['a.js' => 'url_a', 'b.js' => 'url_b']
            );

        $this->assertSame('url_string', vite('main.js'));

        $resultArray = vite('a.js', 'b.js');
        $this->assertIsArray($resultArray);
        $this->assertCount(2, $resultArray);
        $this->assertSame('url_a', $resultArray['a.js']);
    }

    public function testCanGetResponseFromContainer(): void
    {
        $app = new Application(__DIR__);

        $app->set(
            TemplatorFinder::class,
            fn () => new TemplatorFinder([__DIR__ . '/fixtures/support/view'], ['.php'])
        );

        $app->set(
            'view.instance',
            fn (TemplatorFinder $finder) => new Templator($finder, __DIR__ . '/fixtures/support/cache')
        );

        $app->set(
            'view.response',
            fn () => function (string $viewPath, array $portal = []) use ($app): Response {
                /** @var Templator $templator */
                $templator = $app->make(Templator::class);

                /** @var array<string, mixed> $portal */
                return new Response($templator->render($viewPath, $portal));
            }
        );

        $view = view('test', [], ['status' => 500]);
        $this->assertEquals(500, $view->getStatusCode());

        $content = $view->getContent();
        if (!is_string($content)) {
            $this->fail('Expected string view content.');
        }
        $this->assertTrue(Str::contains($content, 'omega'));

        $app->flush();
    }
}
