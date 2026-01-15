<?php

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Tests\Console\Style;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Omega\Console\Style\ProgressBar;
use Omega\Text\Str;

use function ob_get_clean;
use function ob_start;
use function range;

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
