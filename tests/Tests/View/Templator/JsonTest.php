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
 * Test suite for the JsonTemplator.
 *
 * Ensures that `{% JSON %}` directives generate correct JSON output
 * and handle optional encoding parameters properly.
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
final class JsonTest extends TestCase
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
     * Test it can render JSON.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderJson(): void
    {
        $out = $this->templator->templates('<html><head></head><body>{% json($data) %}</body></html>');
        $this->assertEquals(
            '<html><head></head><body><?php echo json_encode('
            . '$data, 0 | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR, 512'
            . '); ?></body></html>',
            $out
        );
    }

    /**
     * Test it can render JSON with optional params.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderJsonWithOptionalParam(): void
    {
        $out = $this->templator->templates('<html><head></head><body>{% json($data, 1, 500) %}</body></html>');
        $this->assertEquals(
            '<html><head></head><body><?php echo json_encode('
            . '$data, 1 | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR, 500'
            . '); ?></body></html>',
            $out
        );
    }
}
