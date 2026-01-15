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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;

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
    /**
     * Test it can render JSON.
     *
     * @return void
     * @throws Exception If the templator fails to process the template.
     */
    public function testItCanRenderJson(): void
    {
        $templator = new Templator(new TemplatorFinder([__DIR__], ['']), __DIR__);
        $out       = $templator->templates('<html><head></head><body>{% json($data) %}</body></html>');
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
        $templator = new Templator(new TemplatorFinder([__DIR__], ['']), __DIR__);
        $out       = $templator->templates('<html><head></head><body>{% json($data, 1, 500) %}</body></html>');
        $this->assertEquals(
            '<html><head></head><body><?php echo json_encode('
            . '$data, 1 | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_THROW_ON_ERROR, 500'
            . '); ?></body></html>',
            $out
        );
    }
}
