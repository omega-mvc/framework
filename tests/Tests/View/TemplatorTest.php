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

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Text\Str;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use Throwable;

use function glob;
use function is_file;
use function md5;
use function substr_count;
use function trim;
use function unlink;

use const DIRECTORY_SEPARATOR;

/**
 * Test suite for the Templator rendering engine.
 *
 * This class verifies the full template lifecycle, including compilation,
 * caching, rendering, inclusion, control structures, variables, sections,
 * slots, comments, raw blocks, and error handling.
 *
 * It also ensures that templates behave consistently with and without cache,
 * and that the Templator integrates correctly with the TemplatorFinder.
 *
 * @category  Tests
 * @package   View
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversClass(Str::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
class TemplatorTest extends TestCase
{
    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $files = glob(__DIR__ . '/caches/*.php');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Assert that the given text contains the expected substring.
     *
     * This helper method is used to improve test readability when checking
     * that a rendered template includes specific output fragments.
     *
     * @param string $text The rendered output to inspect.
     * @param string $find The substring expected to be present.
     *
     * @return void
     */
    private function assertSee(string $text, string $find): void
    {
        $this->assertTrue(Str::contains($text, $find));
    }

    /**
     * Assert that the given text does NOT contain the specified substring.
     *
     * This helper method is mainly used to verify that certain template elements
     * (such as comments or skipped blocks) are not rendered in the final output.
     *
     * @param string $text The rendered output to inspect.
     * @param string $find The substring that must not be present.
     * @return void
     * @noinspection PhpSameParameterValueInspection
     */
    private function assertBlind(string $text, string $find): void
    {
        $this->assertTrue(!Str::contains($text, $find));
    }

    /**
     * Test it can render php templare.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderPhpTemplate(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('php.php', []);

        $this->assertEquals('<html><head></head><body>taylor</body></html>', trim($out));

        // without cache
        $out  = $view->render('php.php', [], false);
        $this->assertEquals('<html><head></head><body>taylor</body></html>', trim($out));
    }

    /**
     * est it can render include template.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderIncludeTemplate(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('include.php', []);

        $this->assertSee(trim($out), '<p>taylor</p>');

        // without cache
        $out  = $view->render('include.php', [], false);
        $this->assertSee(trim($out), '<p>taylor</p>');
    }

    /**
     * Test it can render include nesting template.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderIncludeNestingTemplate(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('nesting.include.php', []);

        $this->assertSee(trim($out), '<p>taylor</p>');

        // without cache
        $out  = $view->render('nesting.include.php', [], false);
        $this->assertSee(trim($out), '<p>taylor</p>');
    }

    /**
     * Test it can render name template.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderNameTemplate(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('naming.php', ['name' => 'taylor', 'age' => 17]);

        $this->assertEquals('<html><head></head><body><h1>your taylor, ages 17 </h1></body></html>', trim($out));

        // without cache
        $out  = $view->render('naming.php', ['name' => 'taylor', 'age' => 17], false);
        $this->assertEquals('<html><head></head><body><h1>your taylor, ages 17 </h1></body></html>', trim($out));
    }

    /**
     * Test it can render name template with ternary.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderNameTemplateWithTernary(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('naming-ternary.php', ['age' => false]);

        $this->assertEquals('<html><head></head><body><h1>your nuno, ages 28 </h1></body></html>', trim($out));

        // without cache
        $out  = $view->render('naming-ternary.php', ['age' => false], false);
        $this->assertEquals('<html><head></head><body><h1>your nuno, ages 28 </h1></body></html>', trim($out));
    }

    /**
     * Test it can render name template in sub folder.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderNameTemplateInSubFolder(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('Groups/nesting.php', ['name' => 'taylor', 'age' => 17]);

        $this->assertEquals('<html><head></head><body><h1>your taylor, ages 17 </h1></body></html>', trim($out));
    }

    /**
     * Test it can render if template.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderIfTemplate(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('if.php', ['true' => true]);

        $this->assertEquals('<html><head></head><body><h1> show </h1><h1></h1></body></html>', trim($out));

        // without cache
        $out  = $view->render('if.php', ['true' => true], false);
        $this->assertEquals('<html><head></head><body><h1> show </h1><h1></h1></body></html>', trim($out));
    }

    /**
     * Test it can render else if template.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderElseIfTemplate(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('else.php', ['true' => false]);

        $this->assertEquals('<html><head></head><body><h1> hide </body></html>', trim($out));

        // without cache
        $out  = $view->render('else.php', ['true' => false], false);
        $this->assertEquals('<html><head></head><body><h1> hide </body></html>', trim($out));
    }

    /**
     * Test it can render each template.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderEachTemplate(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('each.php', ['numbers' => [1, 2, 3]]);

        $this->assertEquals('<html><head></head><body>123</body></html>', trim($out));

        // without cache
        $out  = $view->render('each.php', ['numbers' => [1, 2, 3]], false);
        $this->assertEquals('<html><head></head><body>123</body></html>', trim($out));
    }

    /**
     * Test it can render section template.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderSectionTemplate(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

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

    /**
     * Test it can throw error section template.
     *
     * @return void
     */
    public function testItCanThrowErrorSectionTemplate(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

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

    /**
     * Test it can render template.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderTemplate(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

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

    /**
     * Test it can render comment template.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderCommentTemplate(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('comment.php', []);

        $this->assertBlind($out, 'this a comment');

        // without cache
        $out  = $view->render('comment.php', [], false);
        $this->assertBlind($out, 'this a comment');
    }

    /**
     * Test it can render repeat template.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderRepeatTemplate(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('repeat.include.php', []);

        $this->assertEquals(6, substr_count($out, 'some text'));

        // without cache
        $out  = $view->render('repeat.include.php', [], false);
        $this->assertEquals(6, substr_count($out, 'some text'));
    }

    /**
     * Test it can compile template file.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanCompileTemplateFile(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->compile('include.php');

        $this->assertSee(trim($out), '<p>taylor</p>');
        $this->assertFileExists($cache . DIRECTORY_SEPARATOR . md5('include.php') . '.php');
    }

    /**
     * Test it can compile set template.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanCompileSetTemplate(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->compile('set.php');

        $contain = Str::contains($out, '<?php $foo = \'bar\'; ?>');
        $this->assertTrue($contain);
        $contain = Str::contains($out, '<?php $bar = 123; ?>');
        $this->assertTrue($contain);
        $contain = Str::contains($out, '<?php $arr = [12, \'34\']; ?>');
        $this->assertTrue($contain);
    }

    /**
     * Test it can render name template with raw.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderNameTemplateWithRaw(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('namingskip.php', ['render' => 'oke']);

        $this->assertEquals(
            '<html><head></head><body><h1>oke, your {{ name }}, ages {{ age }}</h1></body></html>',
            trim($out)
        );
    }

    /**
     * Test it can render each break template.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderEachBreakTemplate(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('eachbreak.php', ['numbers' => [1, 2, 3]]);

        $this->assertEquals('<html><head></head><body></body></html>', trim($out));
    }

    /**
     * Test it can render each continue template.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanRenderEachContinueTemplate(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('eachcontinue.php', ['numbers' => [1, 2, 3]]);

        $this->assertEquals('<html><head></head><body></body></html>', trim($out));
    }

    /**
     * Test it can get raw parameter data.
     *
     * @return void
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItCanGetRawParameterData(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);
        $out  = $view->render('parent-data.php', ['full.name' => 'taylor otwell']);

        $this->assertEquals(
            '<html><head></head><body><h1>my name is taylor otwell </h1></body></html>',
            trim($out)
        );
    }

    /**
     * Test it can check template file exist.
     *
     * @return void
     */
    public function testItCanCheckTemplateFileExist(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $view = new Templator(new TemplatorFinder([$loader], ['']), $cache);

        $this->assertTrue($view->viewExist('php.php'));
        $this->assertFalse($view->viewExist('notexist.php'));
    }

    /**
     * Test it can make templator using string.
     *
     * @return void
     * @noinspection PhpConditionAlreadyCheckedInspection
     */
    public function testItCanMakeTemplatorUsingString(): void
    {
        $loader  = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache   = __DIR__ . DIRECTORY_SEPARATOR . 'caches';

        $template = new Templator($loader, $cache);
        $this->assertInstanceOf(Templator::class, $template);
        $finder = (fn () => $this->{'finder'})->call($template);
        $this->assertEquals(['.template.php', '.php'], $finder->getExtensions());
        $this->assertEquals([$loader], $finder->getPaths());
    }

    /**
     * Test it can set new finder.
     *
     * @return void
     */
    public function testItCanSetNewFinder()
    {
        $loader     = __DIR__ . DIRECTORY_SEPARATOR . 'sample' . DIRECTORY_SEPARATOR . 'Templators';
        $cache      = __DIR__ . DIRECTORY_SEPARATOR . 'caches';
        $finder     = new TemplatorFinder([$loader]);
        $templator  = new Templator(new TemplatorFinder([$loader], ['.php']), $cache);
        $get_finder = (fn () => $this->{'finder'})->call($templator);
        $this->assertNotSame($finder, $get_finder);

        $templator->setFinder($finder);
        $get_finder = (fn () => $this->{'finder'})->call($templator);
        $this->assertSame($finder, $get_finder);
    }
}
