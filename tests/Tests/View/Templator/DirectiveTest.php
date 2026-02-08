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
use Omega\View\Exceptions\DirectiveCanNotBeRegisterException;
use Omega\View\Exceptions\DirectiveNotRegisterException;
use Omega\View\Templator;
use Omega\View\Templator\DirectiveTemplator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;

/**
 * Test suite for the DirectiveTemplator.
 *
 * Ensures that custom directives can be registered, called, and
 * that exceptions are thrown when directives are missing or not allowed.
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
#[CoversClass(DirectiveCanNotBeRegisterException::class)]
#[CoversClass(DirectiveNotRegisterException::class)]
#[CoversClass(Templator::class)]
#[CoversClass(DirectiveTemplator::class)]
#[CoversClass(TemplatorFinder::class)]
final class DirectiveTest extends TestCase
{
    use FixturesPathTrait;

    /**
     * Instance of the Templator class used to render template strings
     * for testing purposes. It wraps a TemplatorFinder that manages
     * template paths and extensions.
     *
     * @var Templator
     */
    private Templator $templator;

    /**
     * Sets up the environment before each test method.
     *
     * This method is called automatically by PHPUnit before each test runs.
     * It is responsible for initializing the application instance, setting up
     * dependencies, and preparing any state required by the test.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->templator = new Templator(
            new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/view/')], ['']),
            $this->setFixturePath('/fixtures/view/templator/')
        );
    }

    /**
     * test it cqn render each break
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderEachBreak(): void
    {
        DirectiveTemplator::register('sum', fn ($a, $b): int => $a + $b);
        $out = $this->templator->templates('<html><head></head><body>{% sum(1, 2) %}</body></html>');
        $this->assertEquals(
            "<html><head></head><body>"
            . "<?php echo Omega\View\Templator\DirectiveTemplator::call('sum', 1, 2); ?>"
            . "</body></html>",
            $out
        );
    }

    /**
     * Test it throw exception dur directive not register.
     *
     * @return void
     */
    public function testItThrowExceptionDueDirectiveNotRegister(): void
    {
        $this->expectException(DirectiveNotRegisterException::class);
        DirectiveTemplator::call('unknow', 0);
    }

    /**
     * Test it cn not register directive.
     *
     * @return void
     */
    public function testItCanNotRegisterDirective(): void
    {
        $this->expectException(DirectiveCanNotBeRegisterException::class);
        DirectiveTemplator::register('include', fn ($file): string => $file);
    }

    /**
     * Test it can register and call directive.
     *
     * @return void
     */
    public function testItCanRegisterAndCallDirective(): void
    {
        DirectiveTemplator::register('sum', fn ($a, $b): int => $a + $b);
        $this->assertEquals(2, DirectiveTemplator::call('sum', 1, 1));
    }
}
