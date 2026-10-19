<?php

declare(strict_types=1);

namespace Tests\View;

use Omega\Text\Str;
use Omega\View\Exceptions\ViewFileNotFoundException;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Tests\TestCase;
use Throwable;

use function chmod;
use function file_exists;
use function glob;
use function is_file;
use function md5;
use function ob_get_level;
use function substr_count;
use function trim;
use function unlink;

#[CoversClass(Str::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
#[CoversClass(ViewFileNotFoundException::class)]
final class TemplatorTest extends TestCase
{
    protected function tearDown(): void
    {
        $files = glob(__DIR__ . '/fixtures/view/caches/*.php');
        if ($files !== false) {
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        parent::tearDown();
    }

    private function assertSee(string $text, string $find): void
    {
        $this->assertTrue(Str::contains($text, $find));
    }

    private function assertBlind(string $text, string $find): void
    {
        $this->assertFalse(Str::contains($text, $find));
    }

    public function testCanSetNewFinder(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $finder     = new TemplatorFinder([$loader]);
        $templator  = new Templator(new TemplatorFinder([$loader], ['.php']), $cache);
        $get_finder = (fn () => $this->{'finder'})->call($templator);
        $this->assertNotSame($finder, $get_finder);

        $templator->setFinder($finder);
        $get_finder = (fn () => $this->{'finder'})->call($templator);
        $this->assertSame($finder, $get_finder);
    }

    public function testCanCompileTemplateFile(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches/';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->compile('include.php');

        $this->assertSee(trim($out), '<p>taylor</p>');
        $this->assertFileIsReadable($cache . md5('include.php') . '.php');
    }

    public function testCanCompileSetTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->compile('set.php');

        $this->assertTrue(Str::contains($out, '<?php $foo = \'bar\'; ?>'));
        $this->assertTrue(Str::contains($out, '<?php $bar = 123; ?>'));
        $this->assertTrue(Str::contains($out, '<?php $arr = [12, \'34\']; ?>'));
    }

    public function testCanRenderPhpTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('php.php', []);

        $this->assertEquals('<html><head></head><body>taylor</body></html>', trim($out));

        $out = $view->render('php.php', [], false);
        $this->assertEquals('<html><head></head><body>taylor</body></html>', trim($out));
    }

    public function testCanRenderIncludeTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('include.php', []);

        $this->assertSee(trim($out), '<p>taylor</p>');

        $out = $view->render('include.php', [], false);
        $this->assertSee(trim($out), '<p>taylor</p>');
    }

    public function testCanRenderIncludeNestingTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('nesting.include.php', []);

        $this->assertSee(trim($out), '<p>taylor</p>');

        $out = $view->render('nesting.include.php', [], false);
        $this->assertSee(trim($out), '<p>taylor</p>');
    }

    public function testCanRenderNameTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('naming.php', ['name' => 'taylor', 'age' => 17]);

        $this->assertEquals('<html><head></head><body><h1>your taylor, ages 17 </h1></body></html>', trim($out));

        $out = $view->render('naming.php', ['name' => 'taylor', 'age' => 17], false);
        $this->assertEquals('<html><head></head><body><h1>your taylor, ages 17 </h1></body></html>', trim($out));
    }

    public function testCanRenderNameTemplateWithTernary(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('naming-ternary.php', ['age' => false]);

        $this->assertEquals('<html><head></head><body><h1>your nuno, ages 28 </h1></body></html>', trim($out));

        $out = $view->render('naming-ternary.php', ['age' => false], false);
        $this->assertEquals('<html><head></head><body><h1>your nuno, ages 28 </h1></body></html>', trim($out));
    }

    public function testCanRenderNameTemplateInSubFolder(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('Groups/nesting.php', ['name' => 'taylor', 'age' => 17]);

        $this->assertEquals('<html><head></head><body><h1>your taylor, ages 17 </h1></body></html>', trim($out));
    }

    public function testCanRenderIfTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('if.php', ['true' => true]);

        $this->assertEquals('<html><head></head><body><h1> show </h1><h1></h1></body></html>', trim($out));

        // without cache
        $out = $view->render('if.php', ['true' => true], false);
        $this->assertEquals('<html><head></head><body><h1> show </h1><h1></h1></body></html>', trim($out));
    }

    public function testCanRenderElseIfTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('else.php', ['true' => false]);

        $this->assertEquals('<html><head></head><body><h1> hide </body></html>', trim($out));

        // without cache
        $out = $view->render('else.php', ['true' => false], false);
        $this->assertEquals('<html><head></head><body><h1> hide </body></html>', trim($out));
    }

    public function testCanRenderEachTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('each.php', ['numbers' => [1, 2, 3]]);

        $this->assertEquals('<html><head></head><body>123</body></html>', trim($out));

        // without cache
        $out = $view->render('each.php', ['numbers' => [1, 2, 3]], false);
        $this->assertEquals('<html><head></head><body>123</body></html>', trim($out));
    }

    public function testCanRenderSectionTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('slot.php', [
            'title'   => 'taylor otwell',
            'product' => 'laravel',
            'year'    => 2023,
        ]);

        $this->assertSee($out, 'taylor otwell');
        $this->assertSee($out, 'laravel');
        $this->assertSee($out, '2023');
    }

    public function testCanThrowErrorSectionTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);

        try {
            $view->render('slot_miss.php', [
                'title'   => 'taylor otwell',
                'product' => 'laravel',
                'year'    => 2023,
            ]);
        } catch (Throwable $th) {
            $this->assertEquals("Slot with extends 'Slots/layout.php' required 'title'", $th->getMessage());
        }
    }

    public function testCanRenderTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view         = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $view->suffix = '.php';
        $out          = $view->render('portfolio', [
            'title'    => 'cool portfolio',
            'products' => ['laravel', 'forge'],
        ]);

        $this->assertSee($out, 'cool portfolio');
        $this->assertSee($out, 'taylor');
        $this->assertSee($out, 'laravel');
        $this->assertSee($out, 'forge');
    }

    public function testCanRenderCommentTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('comment.php', []);

        $this->assertBlind($out, 'this a comment');

        // without cache
        $out = $view->render('comment.php', [], false);
        $this->assertBlind($out, 'this a comment');
    }

    public function testCanRenderRepeatTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('repeat.include.php', []);

        $this->assertEquals(6, substr_count($out, 'some text'));

        // without cache
        $out = $view->render('repeat.include.php', [], false);
        $this->assertEquals(6, substr_count($out, 'some text'));
    }

    public function testCanRenderNameTemplateWithRaw(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('namingskip.php', ['render' => 'oke']);

        $this->assertEquals(
            '<html><head></head><body><h1>oke, your {{ name }}, ages {{ age }}</h1></body></html>',
            trim($out)
        );
    }

    public function testCanRenderEachBreakTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('eachbreak.php', ['numbers' => [1, 2, 3]]);

        $this->assertEquals('<html><head></head><body></body></html>', trim($out));
    }

    public function testCanRenderEachContinueTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('eachcontinue.php', ['numbers' => [1, 2, 3]]);

        $this->assertEquals('<html><head></head><body></body></html>', trim($out));
    }

    public function testCanGetRawParameterData(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('parent-data.php', ['full.name' => 'taylor otwell']);

        $this->assertEquals(
            '<html><head></head><body><h1>my name is taylor otwell </h1></body></html>',
            trim($out)
        );
    }

    public function testCanCheckTemplateFileExist(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);

        $this->assertTrue($view->viewExist('php.php'));
        $this->assertFalse($view->viewExist('notexist.php'));
    }

    public function testCanMakeTemplatorUsingString(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $template = new Templator($loader, $cache);
        $this->assertInstanceOf(Templator::class, $template);
        /** @var TemplatorFinder $finder */
        $finder = (fn () => $this->{'finder'})->call($template);
        $this->assertEquals(['.template.php', '.php'], $finder->getExtensions());
        $this->assertEquals([$loader], $finder->getPaths());
    }

    public function testPrependDependencyWithExistingChild(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $templator = new Templator(new TemplatorFinder([$loader], ['']), $cache);

        $parent = 'parent.php';
        $child  = 'child.php';
        $templator->addDependency($parent, $child, 1);

        $templator->prependDependency($parent, [$child => 5]);

        /** @var array<string, array<string, int>> $dependencies */
        $dependencies = (fn () => $this->{'dependency'})->call($templator);
        $this->assertEquals(5, $dependencies[$parent][$child]);
    }

    public function testPrependDependencyDoesNotOverrideADeeperExistingDepth(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $templator = new Templator(new TemplatorFinder([$loader], ['']), $cache);

        $parent = 'parent.php';
        $child  = 'child.php';
        $templator->addDependency($parent, $child, 10);

        $templator->prependDependency($parent, [$child => 5]);

        $this->assertSame(['child.php' => 10], $templator->getDependency($parent));
    }

    public function testGetViewCleansBufferOnThrowable(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);

        $badTemplate = $loader . '/bad.php';
        file_put_contents($badTemplate, '<?php throw new RuntimeException("boom");');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessageIsOrContains('boom');

            $view->render('bad.php', []);
        } finally {
            unlink($badTemplate);
        }
    }

    public function testRenderCacheLogicBranches(): void
    {
        $loader       = __DIR__ . '/fixtures/view/sample/Templators';
        $cacheDir     = __DIR__ . '/fixtures/view/caches';
        $view         = new Templator(new TemplatorFinder([$loader], ['']), $cacheDir);
        $template     = 'php.php';
        $templatePath = $loader . '/' . $template;
        $cachePath    = $cacheDir . '/' . md5($template) . '.php';

        // SCENARIO 1: Cache abilitata ma file non esistente (Colpisce file_exists == false)
        $view->render($template, [], true);
        $this->assertFileIsReadable($cachePath);

        // SCENARIO 2: Cache abilitata, file esistente e timestamp valido (HIT DELLA CACHE)
        touch($templatePath, time() - 100);
        touch($cachePath, time());
        $out = $view->render($template, [], true);
        $this->assertSee($out, 'taylor');

        // SCENARIO 3: Cache abilitata, file esistente MA timestamp scaduto (Cache obsoleta)
        touch($templatePath, time());
        touch($cachePath, time() - 100);
        $out = $view->render($template, [], true);
        $this->assertSee($out, 'taylor');

        // SCENARIO 4: Cache disabilitata esplicitamente tramite parametro
        $out = $view->render($template, [], false);
        $this->assertSee($out, 'taylor');
    }

    public function testRenderHitsTheCacheWhenTemplateAndCacheShareTheSameModificationTime(): void
    {
        $loader       = __DIR__ . '/fixtures/view/sample/Templators';
        $cache        = __DIR__ . '/fixtures/view/caches';
        $view         = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $template     = 'equal-mtime.php';
        $templatePath = $loader . '/' . $template;
        $cachePath    = $cache . '/' . md5($template) . '.php';

        file_put_contents($templatePath, '<p>equal-mtime</p>');

        try {
            $view->render($template, [], true);

            $now = time();
            touch($templatePath, $now);
            touch($cachePath, $now);

            $out = $view->render($template, [], true);
            $this->assertSee($out, 'equal-mtime');
        } finally {
            if (file_exists($cachePath)) {
                unlink($cachePath);
            }
            unlink($templatePath);
        }
    }

    public function testRenderKeepsHittingTheCacheOnConsecutiveCalls(): void
    {
        $loader       = __DIR__ . '/fixtures/view/sample/Templators';
        $cache        = __DIR__ . '/fixtures/view/caches';
        $view         = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $template     = 'repeated-hit.php';
        $templatePath = $loader . '/' . $template;
        $cachePath    = $cache . '/' . md5($template) . '.php';

        file_put_contents($templatePath, '<p>repeated-hit</p>');

        try {
            $view->render($template, [], true);

            touch($templatePath, time() - 100);
            touch($cachePath, time());

            $this->assertSee($view->render($template, [], true), 'repeated-hit');
            $this->assertSee($view->render($template, [], true), 'repeated-hit');
        } finally {
            if (file_exists($cachePath)) {
                unlink($cachePath);
            }
            unlink($templatePath);
        }
    }

    public function testCanClearDependencies(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $templator = new Templator(new TemplatorFinder([$loader], ['']), $cache);

        $templator->addDependency('parent.php', 'child.php', 1);
        $this->assertSame(['child.php' => 1], $templator->getDependency('parent.php'));

        $result = $templator->clearDependencies();

        $this->assertSame([], $templator->getDependency('parent.php'));
        $this->assertSame($templator, $result);
    }

    public function testRenderReturnsEmptyStringWhenTemplateUnreadable(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);

        $badTemplate = $loader . '/unreadable.php';
        file_put_contents($badTemplate, 'anything');
        chmod($badTemplate, 0000);

        set_error_handler(static fn (): bool => true);
        try {
            $out = $view->render('unreadable.php', []);
        } finally {
            restore_error_handler();
            chmod($badTemplate, 0644);
            unlink($badTemplate);
        }

        $this->assertSame('', $out);
    }

    public function testRenderEmptyStringWhenTemplateUnreadableWithCacheDisabled(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);

        $badTemplate = $loader . '/unreadable_no_cache.php';
        file_put_contents($badTemplate, 'anything');
        chmod($badTemplate, 0000);

        set_error_handler(static fn (): bool => true);
        try {
            $out = $view->render('unreadable_no_cache.php', [], false);
        } finally {
            restore_error_handler();
            chmod($badTemplate, 0644);
            unlink($badTemplate);
        }

        $this->assertSame('', $out);
    }

    public function testRenderCompilesAFreshTemplateWhenCacheIsDisabledAndNoCacheExists(): void
    {
        $loader       = __DIR__ . '/fixtures/view/sample/Templators';
        $cache        = __DIR__ . '/fixtures/view/caches';
        $view         = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $template     = 'no-cache-fresh.php';
        $templatePath = $loader . '/' . $template;
        $cachePath    = $cache . '/' . md5($template) . '.php';

        file_put_contents($templatePath, '<p>fresh-no-cache</p>');

        try {
            $out = $view->render($template, [], false);
            $this->assertSee($out, 'fresh-no-cache');
        } finally {
            if (file_exists($cachePath)) {
                unlink($cachePath);
            }
            unlink($templatePath);
        }
    }

    public function testGetViewStripsOutputWhenTheBufferHasBeenClosedByTheTemplate(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);

        $badTemplate = $loader . '/closed_buffer.php';
        file_put_contents($badTemplate, '<?php ob_end_clean();');

        try {
            $out = $view->render('closed_buffer.php', []);
        } finally {
            unlink($badTemplate);
        }

        $this->assertSame('', $out);
    }

    public function testGetViewCleansNestedBuffersStartedByTheTemplateOnThrowable(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);

        $badTemplate = $loader . '/nested_buffer.php';
        file_put_contents($badTemplate, '<?php ob_start(); echo "nested"; throw new RuntimeException("nested boom");');

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessageIsOrContains('nested boom');

            $view->render('nested_buffer.php', []);
        } finally {
            unlink($badTemplate);
        }
    }

    public function testGetViewLeavesNoStrayBufferWhenTheTemplateClosedItBeforeThrowing(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);

        $badTemplate = $loader . '/closed_then_throw.php';
        file_put_contents($badTemplate, '<?php ob_end_clean(); throw new RuntimeException("closed boom");');

        $level = ob_get_level();

        try {
            $view->render('closed_then_throw.php', []);
            $this->fail('expected the template throwable to propagate');
        } catch (RuntimeException $e) {
            $this->assertSame('closed boom', $e->getMessage());
        } finally {
            unlink($badTemplate);
        }

        $this->assertSame($level, ob_get_level());
    }

    public function testCompileReturnsEmptyStringWhenTemplateUnreadable(): void
    {
        $loader = __DIR__ . '/fixtures/view/sample/Templators';
        $cache  = __DIR__ . '/fixtures/view/caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);

        $badTemplate = $loader . '/unreadable_compile.php';
        file_put_contents($badTemplate, 'anything');
        chmod($badTemplate, 0000);

        set_error_handler(static fn (): bool => true);
        try {
            $out = $view->compile('unreadable_compile.php');
        } finally {
            restore_error_handler();
            chmod($badTemplate, 0644);
            unlink($badTemplate);
        }

        $this->assertSame('', $out);
    }
}
