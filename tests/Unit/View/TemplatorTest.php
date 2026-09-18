<?php

declare(strict_types=1);

namespace Tests\View;

use Exception;
use Omega\Text\Str;
use Omega\View\Exceptions\ViewFileNotFoundException;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use RuntimeException;
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


covers(Str::class);
covers(Templator::class);
covers(TemplatorFinder::class);
covers(ViewFileNotFoundException::class);

function assertSee(string $text, string $find): void
{
    expect(Str::contains($text, $find))->toBeTrue();
}

function assertBlind(string $text, string $find): void
{
    expect(Str::contains($text, $find))->toBeFalse();
}

afterEach(function (): void {
    $files = glob(__DIR__ . '/fixtures/view/caches/*.php');
    if ($files === false) {
        return;
    }
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
        }
    }
});

it('can set new finder', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $finder     = new TemplatorFinder([$loader]);
    $templator  = new Templator(new TemplatorFinder([$loader], ['.php']), $cache);
    $get_finder = (fn () => $this->{'finder'})->call($templator);
    expect($get_finder)->not->toBe($finder);

    $templator->setFinder($finder);
    $get_finder = (fn () => $this->{'finder'})->call($templator);
    expect($get_finder)->toBe($finder);
});

it('can compile template file', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches/';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->compile('include.php');

    assertSee(trim($out), '<p>taylor</p>');
    expect($cache . md5('include.php') . '.php')->toBeReadableFile();
});

it('can compile set template', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->compile('set.php');

    expect(Str::contains($out, '<?php $foo = \'bar\'; ?>'))->toBeTrue();
    expect(Str::contains($out, '<?php $bar = 123; ?>'))->toBeTrue();
    expect(Str::contains($out, '<?php $arr = [12, \'34\']; ?>'))->toBeTrue();
});

it('can render php template', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('php.php', []);

    expect(trim($out))->toEqual('<html><head></head><body>taylor</body></html>');

    $out  = $view->render('php.php', [], false);
    expect(trim($out))->toEqual('<html><head></head><body>taylor</body></html>');
});

it('can render include template', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('include.php', []);

    assertSee(trim($out), '<p>taylor</p>');

    $out  = $view->render('include.php', [], false);
    assertSee(trim($out), '<p>taylor</p>');
});

it('can render include nesting template', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('nesting.include.php', []);

    assertSee(trim($out), '<p>taylor</p>');

    $out  = $view->render('nesting.include.php', [], false);
    assertSee(trim($out), '<p>taylor</p>');
});

it('can render name template', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('naming.php', ['name' => 'taylor', 'age' => 17]);

    expect(trim($out))->toEqual('<html><head></head><body><h1>your taylor, ages 17 </h1></body></html>');

    $out  = $view->render('naming.php', ['name' => 'taylor', 'age' => 17], false);
    expect(trim($out))->toEqual('<html><head></head><body><h1>your taylor, ages 17 </h1></body></html>');
});

it('can render name template with ternary', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('naming-ternary.php', ['age' => false]);

    expect(trim($out))->toEqual('<html><head></head><body><h1>your nuno, ages 28 </h1></body></html>');

    $out  = $view->render('naming-ternary.php', ['age' => false], false);
    expect(trim($out))->toEqual('<html><head></head><body><h1>your nuno, ages 28 </h1></body></html>');
});

it('can render name template in sub folder', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('Groups/nesting.php', ['name' => 'taylor', 'age' => 17]);

    expect(trim($out))->toEqual('<html><head></head><body><h1>your taylor, ages 17 </h1></body></html>');
});

it('can render if template', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('if.php', ['true' => true]);

    expect(trim($out))->toEqual('<html><head></head><body><h1> show </h1><h1></h1></body></html>');

    // without cache
    $out  = $view->render('if.php', ['true' => true], false);
    expect(trim($out))->toEqual('<html><head></head><body><h1> show </h1><h1></h1></body></html>');
});

it('can render else if template', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('else.php', ['true' => false]);

    expect(trim($out))->toEqual('<html><head></head><body><h1> hide </body></html>');

    // without cache
    $out  = $view->render('else.php', ['true' => false], false);
    expect(trim($out))->toEqual('<html><head></head><body><h1> hide </body></html>');
});

it('can render each template', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('each.php', ['numbers' => [1, 2, 3]]);

    expect(trim($out))->toEqual('<html><head></head><body>123</body></html>');

    // without cache
    $out  = $view->render('each.php', ['numbers' => [1, 2, 3]], false);
    expect(trim($out))->toEqual('<html><head></head><body>123</body></html>');
});

it('can render section template', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('slot.php', [
        'title'   => 'taylor otwell',
        'product' => 'laravel',
        'year'    => 2023,
    ]);

    assertSee($out, 'taylor otwell');
    assertSee($out, 'laravel');
    assertSee($out, '2023');
});

it('can throw error section template', function (): void {
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
        expect($th->getMessage())->toEqual("Slot with extends 'Slots/layout.php' required 'title'");
    }
});

it('can render template', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view         = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $view->suffix = '.php';
    $out          = $view->render('portfolio', [
        'title'    => 'cool portfolio',
        'products' => ['laravel', 'forge'],
    ]);

    assertSee($out, 'cool portfolio');
    assertSee($out, 'taylor');
    assertSee($out, 'laravel');
    assertSee($out, 'forge');
});

it('can render comment template', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('comment.php', []);

    assertBlind($out, 'this a comment');

    // without cache
    $out  = $view->render('comment.php', [], false);
    assertBlind($out, 'this a comment');
});

it('can render repeat template', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('repeat.include.php', []);

    expect(substr_count($out, 'some text'))->toEqual(6);

    // without cache
    $out  = $view->render('repeat.include.php', [], false);
    expect(substr_count($out, 'some text'))->toEqual(6);
});

it('can render name template with raw', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('namingskip.php', ['render' => 'oke']);

    expect(trim($out))->toEqual(
        '<html><head></head><body><h1>oke, your {{ name }}, ages {{ age }}</h1></body></html>'
    );
});

it('can render each break template', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('eachbreak.php', ['numbers' => [1, 2, 3]]);

    expect(trim($out))->toEqual('<html><head></head><body></body></html>');
});

it('can render each continue template', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('eachcontinue.php', ['numbers' => [1, 2, 3]]);

    expect(trim($out))->toEqual('<html><head></head><body></body></html>');
});

it('can get raw parameter data', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $out  = $view->render('parent-data.php', ['full.name' => 'taylor otwell']);

    expect(trim($out))->toEqual(
        '<html><head></head><body><h1>my name is taylor otwell </h1></body></html>'
    );
});

it('can check template file exist', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);

    expect($view->viewExist('php.php'))->toBeTrue();
    expect($view->viewExist('notexist.php'))->toBeFalse();
});

it('can make templator using string', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $template = new Templator($loader, $cache);
    $this->assertInstanceOf(Templator::class, $template);
    /** @var TemplatorFinder $finder */
    $finder = (fn () => $this->{'finder'})->call($template);
    expect($finder->getExtensions())->toEqual(['.template.php', '.php']);
    expect($finder->getPaths())->toEqual([$loader]);
});

it('prepend dependency with existing child', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $templator = new Templator(new TemplatorFinder([$loader], ['']), $cache);

    $parent = 'parent.php';
    $child  = 'child.php';
    $templator->addDependency($parent, $child, 1);

    $templator->prependDependency($parent, [$child => 5]);

    /** @var array<string, array<string, int>> $dependencies */
    $dependencies = (fn() => $this->{'dependency'})->call($templator);
    expect($dependencies[$parent][$child])->toEqual(5);
});

it('prepend dependency does not override a deeper existing depth', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $templator = new Templator(new TemplatorFinder([$loader], ['']), $cache);

    $parent = 'parent.php';
    $child  = 'child.php';
    $templator->addDependency($parent, $child, 10);

    $templator->prependDependency($parent, [$child => 5]);

    expect($templator->getDependency($parent))->toBe(['child.php' => 10]);
});

it('get view cleans buffer on throwable', function (): void {
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
});

it('render cache logic branches', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cacheDir = __DIR__ . '/fixtures/view/caches';
    $view = new Templator(new TemplatorFinder([$loader], ['']), $cacheDir);
    $template = 'php.php';
    $templatePath = $loader . '/' . $template;
    $cachePath = $cacheDir . '/' . md5($template) . '.php';

    // SCENARIO 1: Cache abilitata ma file non esistente (Colpisce file_exists == false)
    $view->render($template, [], true);
    expect($cachePath)->toBeReadableFile();

    // SCENARIO 2: Cache abilitata, file esistente e timestamp valido (HIT DELLA CACHE)
    touch($templatePath, time() - 100);
    touch($cachePath, time());
    $out = $view->render($template, [], true);
    assertSee($out, 'taylor');

    // SCENARIO 3: Cache abilitata, file esistente MA timestamp scaduto (Cache obsoleta)
    touch($templatePath, time());
    touch($cachePath, time() - 100);
    $out = $view->render($template, [], true);
    assertSee($out, 'taylor');

    // SCENARIO 4: Cache disabilitata esplicitamente tramite parametro
    $out = $view->render($template, [], false);
    assertSee($out, 'taylor');
});

it('render hits the cache when template and cache share the same modification time', function (): void {
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
        assertSee($out, 'equal-mtime');
    } finally {
        if (file_exists($cachePath)) {
            unlink($cachePath);
        }
        unlink($templatePath);
    }
});

it('render keeps hitting the cache on consecutive calls', function (): void {
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

        assertSee($view->render($template, [], true), 'repeated-hit');
        assertSee($view->render($template, [], true), 'repeated-hit');
    } finally {
        if (file_exists($cachePath)) {
            unlink($cachePath);
        }
        unlink($templatePath);
    }
});

it('can clear dependencies', function (): void {
    $loader = __DIR__ . '/fixtures/view/sample/Templators';
    $cache  = __DIR__ . '/fixtures/view/caches';

    $templator = new Templator(new TemplatorFinder([$loader], ['']), $cache);

    $templator->addDependency('parent.php', 'child.php', 1);
    expect($templator->getDependency('parent.php'))->toBe(['child.php' => 1]);

    $result = $templator->clearDependencies();

    expect($templator->getDependency('parent.php'))->toBe([]);
    expect($result)->toBe($templator);
});

it('render returns empty string when template unreadable', function (): void {
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

    expect($out)->toBe('');
});

it('render empty string when template unreadable with cache disabled', function (): void {
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

    expect($out)->toBe('');
});

it('render compiles a fresh template when cache is disabled and no cache exists', function (): void {
    $loader     = __DIR__ . '/fixtures/view/sample/Templators';
    $cache      = __DIR__ . '/fixtures/view/caches';
    $view       = new Templator(new TemplatorFinder([$loader], ['']), $cache);
    $template   = 'no-cache-fresh.php';
    $templatePath = $loader . '/' . $template;
    $cachePath    = $cache . '/' . md5($template) . '.php';

    file_put_contents($templatePath, '<p>fresh-no-cache</p>');

    try {
        $out = $view->render($template, [], false);
        assertSee($out, 'fresh-no-cache');
    } finally {
        if (file_exists($cachePath)) {
            unlink($cachePath);
        }
        unlink($templatePath);
    }
});

it('get view strips output when the buffer has been closed by the template', function (): void {
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

    expect($out)->toBe('');
});

it('get view cleans nested buffers started by the template on throwable', function (): void {
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
});

it('get view leaves no stray buffer when the template closed it before throwing', function (): void {
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
        expect($e->getMessage())->toBe('closed boom');
    } finally {
        unlink($badTemplate);
    }

    expect(ob_get_level())->toBe($level);
});

it('compile returns empty string when template unreadable', function (): void {
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

    expect($out)->toBe('');
});
