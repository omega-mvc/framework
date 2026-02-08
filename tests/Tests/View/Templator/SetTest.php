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
 * Test suite for the SetTemplator.
 *
 * Ensures that `{% set %}` directives correctly assign values
 * to variables inside templates, supporting different data types.
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
final class SetTest extends TestCase
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
     * Test it can render set string.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderSetString(): void
    {
        $out = $this->templator->templates('{% set $foo=\'bar\' %}');
        $this->assertEquals('<?php $foo = \'bar\'; ?>', $out);
    }

    /**
     * Test it can render set int.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderSetInt(): void
    {
        $out = $this->templator->templates('{% set $bar=123 %}');
        $this->assertEquals('<?php $bar = 123; ?>', $out);
    }

    /**
     * Test it can render set array.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderSetArray(): void
    {
        $out = $this->templator->templates('{% set $arr=[12, \'34\'] %}');
        $this->assertEquals('<?php $arr = [12, \'34\']; ?>', $out);
    }
}
