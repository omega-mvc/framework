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
use Throwable;

use function trim;

/**
 * Test suite for the ComponentTemplator.
 *
 * Verifies that components are correctly parsed, rendered, and that
 * dependencies and nested components behave as expected. Also tests
 * error handling when templates or yield sections are missing.
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
final class ComponentTest extends TestCase
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
     * Test it can render component scope.
     *
     * @return void
     * @throws Exception If a templator fails to process the template.
     */
    public function testItCanRenderComponentScope(): void
    {
        $out = $this->templator->templates(
            '{% component(\'component.template\') %}<main>core component</main>{% endcomponent %}'
        );
        $this->assertEquals('<html><head></head><body><main>core component</main></body></html>', trim($out));
    }

    /**
     * Test it can render nested component scope.
     *
     * @return void
     * @throws Exception If a templator fails to process the template.
     */
    public function testItCanRenderNestedComponentScope(): void
    {
        $out = $this->templator->templates(
            '{% component(\'componentnested.template\') %}card with nest{% endcomponent %}'
        );
        $this->assertEquals(
            '<html><head></head><body><div class="card">card with nest</div>'
            . PHP_EOL
            . '</body></html>',
            trim($out)
        );
    }

    /**
     * Test it can render component scope multiple.
     *
     * @return void
     * @throws Exception If a templator fails to process the template.
     */
    public function testItCanRenderComponentScopeMultiple(): void
    {
        $out = $this->templator->templates(
            '{% component(\'componentcard.template\') %}oke{% endcomponent %} '
            . '{% component(\'componentcard.template\') %}oke 2 {% endcomponent %}'
        );
        $this->assertEquals(
            '<div class="card">oke</div>'
            . PHP_EOL
            . ' <div class="card">oke 2 </div>',
            trim($out)
        );
    }

    /**
     * Test it throw when extend not found.
     *
     * @return void
     * @throws Exception If a templator fails to process the template.
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItThrowWhenExtendNotFound(): void
    {
        try {
            $this->templator->templates(
                '{% component(\'notexits.template\') %}<main>core component</main>{% endcomponent %}'
            );
        } catch (Throwable $th) {
            $this->assertEquals(
                'View file not found: `notexits.template`',
                $th->getMessage()
            );
        }
    }

    /**
     * Test it throw when extend not found yield.
     *
     * @return void
     * @throws Exception If a templator fails to process the template.
     * @throws Throwable If the templator fails to process the template.
     */
    public function testItThrowWhenExtendNotFoundYield(): void
    {
        try {
            $this->templator->templates(
                '{% component(\'componentyield.template\') %}<main>core component</main>{% endcomponent %}'
            );
        } catch (Throwable $th) {
            $this->assertEquals('Yield section not found: `component2.template`', $th->getMessage());
        }
    }

    /**
     * Test it can render component using named parameter.
     *
     * @return void
     * @throws Exception If a templator fails to process the template.
     */
    public function testItCanRenderComponentUsingNamedParameter(): void
    {
        $out = $this->templator->templates(
            '{% component(\'componentnamed.template\', bg:\'bg-red\', size:"md") %}inner text{% endcomponent %}'
        );
        $this->assertEquals('<p class="bg-red md">inner text</p>', trim($out));
    }

    /**
     * Test it can render component opp a process.
     *
     * @return void
     * @throws Exception If a templator fails to process the template.
     */
    public function testItCanRenderComponentOppAProcess(): void
    {
        $templator = $this->templator;
        $templator->setComponentNamespace('Tests\\View\\Templator\\');
        $out = $templator->templates(
            '{% component(\'TestClassComponent\', bg:\'bg-red\', size:"md") %}inner text{% endcomponent %}'
        );
        $this->assertEquals('<p class="bg-red md">inner text</p>', trim($out));
    }

    /**
     * Test it can get dependency view.
     *
     * @return void
     * @throws Exception If a templator fails to process the template.
     */
    public function testItCanGetDependencyView(): void
    {
        $finder    = new TemplatorFinder([$this->setFixturePath('/fixtures/view/templator/view/')], ['']);
        $templator = new Templator($finder, $this->setFixturePath('/fixtures/view/templator/'));
        $templator->templates(
            '{% component(\'component.template\') %}<main>core component</main>{% endcomponent %}',
            'test'
        );
        $this->assertEquals([
            $finder->find('component.template') => 1,
        ], $templator->getDependency('test'));
    }
}
