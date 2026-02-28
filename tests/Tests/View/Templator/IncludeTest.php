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
use Omega\View\Exceptions\ViewFileNotFoundException;
use Omega\View\Templator;
use Omega\View\Templator\IncludeTemplator;
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
#[CoversClass(IncludeTemplator::class)]
#[CoversClass(Templator::class)]
#[CoversClass(TemplatorFinder::class)]
#[CoversClass(ViewFileNotFoundException::class)]
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
            new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/')], ['']),
            $this->setFixturePath('/fixtures/view/templator/')
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
        $finder    = new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator')], ['']);
        $templator = new Templator($finder, $this->setFixturePath('/fixtures/view/templator'));
        $templator->templates('<html><head></head><body>{% include(\'view/component.php\') %}</body></html>', 'test');
        $this->assertEquals([
            $finder->find('view/component.php') => 1,
        ], $templator->getDependency('test'));
    }

    /**
     * Test it throws an exception when the included template does not exist.
     *
     * @return void
     * @throws Exception
     */
    public function testItThrowsExceptionWhenIncludeNotFound(): void
    {
        $this->expectException(ViewFileNotFoundException::class);
        $this->expectExceptionMessage('View file not found: `nonexistent.php`');

        $this->templator->templates(
            '<html>{% include(\'nonexistent.php\') %}</html>'
        );
    }

    /**
     * Test it returns included template immediately when makeDept is 0.
     *
     * @return void
     * @throws Exception
     */
    public function testItReturnsIncludedTemplateWhenDepthZero(): void
    {
        // Recupero direttamente l'IncludeTemplator
        $reflection = new \ReflectionClass($this->templator);
        $property = $reflection->getProperty('finder');
        $property->setAccessible(true);
        $finder = $property->getValue($this->templator);

        $includeTemplator = new \Omega\View\Templator\IncludeTemplator($finder, $this->setFixturePath('/fixtures/view/templator/'));
        $includeTemplator->maksDept(0);

        $template = "{% include('view/component.php') %}";
        $out      = $includeTemplator->parse($template);

        // Qui il return diretto viene eseguito senza decrementare makeDept
        $this->assertStringContainsString('<p>Call From Component</p>', $out);
    }
}
