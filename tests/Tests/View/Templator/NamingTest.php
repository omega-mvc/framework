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
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tests\FixturesPathTrait;

/**
 * Test suite for the NameTemplator.
 *
 * Ensures that variable interpolation, escapes, raw output,
 * ternary operators, function calls, and raw blocks are rendered correctly.
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
final class NamingTest extends TestCase
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
            new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/')], ['']),
            $this->setFixturePath('/fixtures/view/templator/')
        );
    }

    /**
     * Test it can render naming.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderNaming(): void
    {
        $out = $this->templator->templates(
            '<html><head></head><body><h1>your {{ $name }}, ages {{ $age }} </h1></body></html>'
        );
        $this->assertEquals(
            '<html><head></head><body><h1>your <?php echo htmlspecialchars($name); ?>, '
            . 'ages <?php echo htmlspecialchars($age); ?> </h1></body></html>',
            $out
        );
    }

    /**
     * Test it can render naming without escape.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderNamingWithoutEscape(): void
    {
        $out = $this->templator->templates(
            '<html><head></head><body><h1>your {!! $name !!}, '
            . 'ages {!! $age !!} </h1></body></html>'
        );
        $this->assertEquals(
            '<html><head></head><body><h1>your <?php echo $name ; ?>, '
            . 'ages <?php echo $age ; ?> </h1></body></html>',
            $out
        );
    }

    /**
     * Test it can render naming with call function.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderNamingWithCallFunction(): void
    {
        $out = $this->templator->templates(
            '<html><head></head><body><h1>time: }{{ now()->timestamp }}</h1></body></html>'
        );
        $this->assertEquals(
            '<html><head></head><body><h1>time: }<?php echo htmlspecialchars(now()->timestamp); ?></h1></body></html>',
            $out
        );
    }

    /**
     * Test it can render naming ternary.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderNamingTernary(): void
    {
        $out = $this->templator->templates(
            '<html><head></head><body><h1>your '
            . '{{ $name ?? \'nuno\' }}, ages '
            . '{{ $age ? 17 : 28 }} </h1></body></html>'
        );
        $this->assertEquals(
            '<html><head></head><body><h1>your '
            . '<?php echo htmlspecialchars($name ?? \'nuno\'); ?>, ages '
            . '<?php echo htmlspecialchars($age ? 17 : 28); ?> </h1>'
            . '</body></html>',
            $out
        );
    }

    /**
     * Test it can render naming skip.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderNamingSkip(): void
    {
        $out = $this->templator->templates(
            '<html><head></head><body><h1>{{ $render }}, '
            . '{% raw %}your {{ name }}, ages {{ age }}{% endraw %}</h1></body></html>'
        );
        $this->assertEquals(
            '<html><head></head><body><h1><?php echo htmlspecialchars($render); ?>, '
            . 'your {{ name }}, ages {{ age }}</h1></body></html>',
            $out
        );
    }
}
