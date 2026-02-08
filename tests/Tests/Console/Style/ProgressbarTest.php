<?php

/**
 * Part of Omega - Tests\Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */
/** @noinspection PhpUnusedLocalVariableInspection */

declare(strict_types=1);

namespace Tests\Console\Style;

use Omega\Console\Style\ProgressBar;
use Omega\Text\Str;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function ob_get_clean;
use function ob_start;
use function range;

/**
 * Class ProgressbarTest
 *
 * This test class validates the functionality of the ProgressBar utility class,
 * which provides a visual representation of progress in the terminal.
 * It tests the correct rendering of the progress bar in default mode
 * and when using custom tick formatting, ensuring that both the visual
 * progress indicators and optional dynamic labels behave as expected.
 *
 * @category   Tests
 * @package    Console
 * @subpackage Style
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html GPL V3.0+
 * @version    2.0.0
 */
#[CoversClass(ProgressBar::class)]
#[CoversClass(Str::class)]
final class ProgressbarTest extends TestCase
{
    /**
     * Test it can render progress bar.
     *
     * @return void
     */
    public function testItCanRenderProgressbar(): void
    {
        $progressbar       = new ProgressBar(':progress');
        $progressbar->mask = 10;
        ob_start();
        foreach (range(1, 10) as $tick) {
            $progressbar->current++;
            $progressbar->tick();
        }
        $out = ob_get_clean();

        $this->assertTrue(Str::contains($out, '[=>------------------]'));
        $this->assertTrue(Str::contains($out, '[=========>----------]'));
        $this->assertTrue(Str::contains($out, '[====================]'));
    }

    /**
     * Test it can render progress bar using custom tick.
     *
     * @return void
     */
    public function testItCanRenderProgressbarUsingCustomTick(): void
    {
        $progressbar       = new ProgressBar(':progress');
        $progressbar->mask = 10;
        ob_start();
        foreach (range(1, 10) as $tick) {
            $progressbar->current++;
            $progressbar->tickWith(':progress :custom', [
                ':custom' => fn (): string => "{$progressbar->current}/{$progressbar->mask}",
            ]);
        }
        $out = ob_get_clean();

        $this->assertTrue(Str::contains($out, '[=>------------------] 1/10'));
        $this->assertTrue(Str::contains($out, '[=========>----------] 5/10'));
        $this->assertTrue(Str::contains($out, '[====================] 10/10'));
    }
}
