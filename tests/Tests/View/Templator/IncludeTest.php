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
 * Test suite for the IncludeTemplator.
 *
 * Ensures that `{% include %}` directives are processed correctly
 * and that dependencies are tracked when including views.
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
final class IncludeTest extends TestCase
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
            new TemplatorFinder([$this->fixturePath('/fixtures/view/templator/')], ['']),
            $this->fixturePath('/fixtures/view/templator/')
        );
    }

    /**
     * Test it can render include.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderInclude(): void
    {
        $out = $this->templator->templates(
            '<html><head></head><body>{% include(\'/view/component.php\') %}</body></html>'
        );
        $this->assertEquals('<html><head></head><body><p>Call From Component</p></body></html>', $out);
    }

    /**
     * Test it can fetch dependency view.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanFetchDependencyView(): void
    {
        $finder    = new TemplatorFinder([$this->fixturePath('/fixtures/view/templator')], ['']);
        $templator = new Templator($finder, $this->fixturePath('/fixtures/view/templator'));
        $templator->templates('<html><head></head><body>{% include(\'view/component.php\') %}</body></html>', 'test');
        $this->assertEquals([
            $finder->find('view/component.php') => 1,
        ], $templator->getDependency('test'));
    }
}
