<?php

declare(strict_types=1);

namespace Tests\Console;

use Omega\Application\Application;
use Omega\Console\ConsoleBranding;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionMethod;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

#[CoversClass(ConsoleBranding::class)]
final class ConsoleBrandingTest extends TestCase
{
    public function testSkipsTheHeaderForSilentCommands(): void
    {
        $app = new Application('/');
        $branding = new ConsoleBranding($app, 'Omega Test:', '1.0.0');
        $output = new BufferedOutput();

        $exit = $branding->doRun(new ArrayInput(['--version']), $output);

        $this->assertSame(0, $exit);
        $this->assertStringNotContainsString('Environment:', $output->fetch());

        $app->flush();
    }

    public function testRendersTheBrandedHeaderForNonSilentCommands(): void
    {
        $app = new Application('/');
        $branding = new ConsoleBranding($app, 'Omega Test:', '1.0.0');
        $branding->addCommand((new Command('greet'))->setCode(static fn (): int => 0));
        $output = new BufferedOutput();

        $exit = $branding->doRun(new ArrayInput(['greet']), $output);
        $display = $output->fetch();

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('____', $display);
        $this->assertStringContainsString('Environment:', $display);
        $this->assertStringContainsString('Debug: OFF', $display);
        $this->assertStringContainsString('Command Cache: NO', $display);

        $app->flush();
    }

    public function testRendersTheDebugStatusWhenDebugModeIsEnabled(): void
    {
        $app = new Application('/');
        $app->set('app.debug', true);
        $branding = new ConsoleBranding($app, 'Omega Test:', '1.0.0');
        $branding->addCommand((new Command('greet'))->setCode(static fn (): int => 0));
        $output = new BufferedOutput();

        $branding->doRun(new ArrayInput(['greet']), $output);

        $this->assertStringContainsString('Debug: ON', $output->fetch());

        $app->flush();
    }

    public function testRendersTheCommandCacheStatusWhenTheCacheFileExists(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omega-branding-' . bin2hex(random_bytes(4));
        $cacheDir = $base . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache';
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'commands.php';
        mkdir($cacheDir, 0777, true);
        file_put_contents($cacheFile, '<?php return [];');

        try {
            $app = new Application($base);
            $branding = new ConsoleBranding($app, 'Omega Test:', '1.0.0');
            $branding->addCommand((new Command('greet'))->setCode(static fn (): int => 0));
            $output = new BufferedOutput();

            $branding->doRun(new ArrayInput(['greet']), $output);

            $this->assertStringContainsString('Command Cache: YES', $output->fetch());
        } finally {
            @unlink($cacheFile);
            @rmdir($cacheDir);
            @rmdir(dirname($cacheDir));
            @rmdir($base);
        }
    }

    public function testRendersBothTheDebugAndCommandCacheStatusAsEnabled(): void
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omega-branding-' . bin2hex(random_bytes(4));
        $cacheDir = $base . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache';
        $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . 'commands.php';
        mkdir($cacheDir, 0777, true);
        file_put_contents($cacheFile, '<?php return [];');

        try {
            $app = new Application($base);
            $app->set('app.debug', true);
            $branding = new ConsoleBranding($app, 'Omega Test:', '1.0.0');
            $branding->addCommand((new Command('greet'))->setCode(static fn (): int => 0));
            $output = new BufferedOutput();

            $branding->doRun(new ArrayInput(['greet']), $output);

            $display = $output->fetch();
            $this->assertStringContainsString('Debug: ON', $display);
            $this->assertStringContainsString('Command Cache: YES', $display);
        } finally {
            @unlink($cacheFile);
            @rmdir($cacheDir);
            @rmdir(dirname($cacheDir));
            @rmdir($base);
        }
    }

    public function testDetectsSilentCommandsByTheirOptions(): void
    {
        $app = new Application('/');
        $branding = new ConsoleBranding($app, 'Omega Test:', '1.0.0');
        $isSilent = new ReflectionMethod(ConsoleBranding::class, 'isSilentCommand');

        foreach (['--version', '-V', '--quiet', '-q'] as $token) {
            $this->assertTrue((bool) $isSilent->invoke($branding, new ArrayInput([$token])));
        }

        $app->flush();
    }

    public function testDetectsSilentCommandsByTheirFirstArgument(): void
    {
        $app = new Application('/');
        $branding = new ConsoleBranding($app, 'Omega Test:', '1.0.0');
        $isSilent = new ReflectionMethod(ConsoleBranding::class, 'isSilentCommand');

        foreach (['list', 'help', 'completion'] as $token) {
            $this->assertTrue((bool) $isSilent->invoke($branding, new ArrayInput([$token])));
        }

        $this->assertFalse((bool) $isSilent->invoke($branding, new ArrayInput(['serve'])));
        $this->assertFalse((bool) $isSilent->invoke($branding, new ArrayInput([])));

        $app->flush();
    }

    public function testFormatsByteCountsInHumanReadableUnits(): void
    {
        $app = new Application('/');
        $branding = new ConsoleBranding($app, 'Omega Test:', '1.0.0');
        $format = new ReflectionMethod(ConsoleBranding::class, 'formatBytes');

        $this->assertSame('0 B', $format->invoke($branding, 0));
        $this->assertSame('500 B', $format->invoke($branding, 500));
        $this->assertSame('1 KB', $format->invoke($branding, 1024));
        $this->assertSame('1 MB', $format->invoke($branding, 1024 * 1024));
        $this->assertSame('1 GB', $format->invoke($branding, 1024 ** 3));
        $this->assertSame('1024 GB', $format->invoke($branding, 1024 ** 4));

        $app->flush();
    }
}
