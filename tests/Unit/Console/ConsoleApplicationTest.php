<?php

declare(strict_types=1);

namespace Tests\Console;

use Omega\Application\Application;
use Omega\Console\CommandLoader;
use Omega\Console\ConsoleApplication;
use Omega\Console\ConsoleBranding;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Tests\Console\Fixtures\ConsoleApplicationHarness;
use Tests\Console\Fixtures\DemoCommand;
use Tests\Console\Fixtures\PlainCommand;
use Tests\TestCase;

#[CoversClass(ConsoleApplication::class)]
final class ConsoleApplicationTest extends TestCase
{
    /**
     * @param array<int, string> $tokens
     */
    private function runHandleInSubprocess(string $autoload, string $base, array $tokens, mixed $input, string $outputCode, string $shell): int
    {
        $inputCode = match (true) {
            $input === null => 'null',
            $input === 'array-input' => 'new \Symfony\Component\Console\Input\ArrayInput(' . var_export($tokens, true) . ')',
            default => var_export($input, true),
        };

        $autoloadCode = var_export($autoload, true);
        $baseCode = var_export($base, true);
        $argvCode = var_export($tokens, true);

        $code = "require {$autoloadCode}; \$_SERVER['argv'] = {$argvCode};"
            . " \$app = new \Omega\Application\Application({$baseCode});"
            . ' $app->bootstrapWith([]);'
            . ' $console = new \Omega\Console\ConsoleApplication($app);'
            . " \$output = {$outputCode};"
            . " \$exit = \$console->handle({$inputCode}, \$output);"
            . ' echo $exit;';

        $env = $shell === '' ? 'SHELL= ' : 'SHELL=' . escapeshellarg($shell) . ' ';
        $command = $env . escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($code);

        exec($command, $lines, $status);

        return $status;
    }

    private function newConsoleBase(): string
    {
        $base = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'omega-console-' . bin2hex(random_bytes(4));
        mkdir($base, 0777, true);

        return $base;
    }

    private function removeConsoleBase(string $base): void
    {
        foreach (['/bootstrap/cache', '/bootstrap', ''] as $suffix) {
            $path = $base . $suffix;
            if (is_dir($path)) {
                rmdir($path);
            }
        }
    }

    public function testBootstrapsTheApplicationWhenItHasNotBeenBootstrappedYet(): void
    {
        $app = new Application('/');
        $console = new ConsoleApplicationHarness($app);

        $console->exposeBootstrap();

        $this->assertTrue($app->bootstrapped);

        $app->flush();
    }

    public function testSkipsBootstrappingWhenTheApplicationIsAlreadyBootstrapped(): void
    {
        $app = new Application('/');
        $app->bootstrapWith([]);
        $console = new ConsoleApplicationHarness($app);

        $console->exposeBootstrap();

        $this->assertTrue($app->bootstrapped);

        $app->flush();
    }

    public function testReturnsAnEmptyMapWhenTheCacheFileDoesNotReturnAnArray(): void
    {
        $base = $this->newConsoleBase();
        $cacheFile = $base . DIRECTORY_SEPARATOR . 'map.php';
        file_put_contents($cacheFile, '<?php return \'not-an-array\';');

        try {
            $app = new Application($base);
            $console = new ConsoleApplicationHarness($app);

            $this->assertSame([], $console->exposeLoadCachedCommands($cacheFile));
        } finally {
            @unlink($cacheFile);
            $this->removeConsoleBase($base);
        }
    }

    public function testKeepsOnlyValidNameToClassEntriesFromTheCacheFile(): void
    {
        $base = $this->newConsoleBase();
        $cacheFile = $base . DIRECTORY_SEPARATOR . 'map.php';
        file_put_contents(
            $cacheFile,
            '<?php return ' . var_export([
                'demo:hello' => DemoCommand::class,
                'missing:class' => 'No\\Such\\Class',
                'not:a-command' => stdClass::class,
                5 => DemoCommand::class,
                'plain:run' => PlainCommand::class,
            ], true) . ';'
        );

        try {
            $app = new Application($base);
            $console = new ConsoleApplicationHarness($app);

            $this->assertSame([
                'demo:hello' => DemoCommand::class,
                'plain:run' => PlainCommand::class,
            ], $console->exposeLoadCachedCommands($cacheFile));
        } finally {
            @unlink($cacheFile);
            $this->removeConsoleBase($base);
        }
    }

    public function testDiscoversCommandsFromTheFrameworkAndApplicationPaths(): void
    {
        require_once __DIR__ . '/Fixtures/DiscoveredCommands/DiscoveryCommand.php';
        require_once __DIR__ . '/Fixtures/DiscoveredCommands/DiscoveryAliasedCommand.php';
        require_once __DIR__ . '/Fixtures/DiscoveredCommands/DiscoveryNoAttributeCommand.php';
        require_once __DIR__ . '/Fixtures/DiscoveredCommands/DiscoveryNotCommand.php';

        $base = $this->newConsoleBase();
        $app = new Application($base);
        $app->set('path.command', __DIR__ . '/Fixtures/DiscoveredCommands');
        $console = new ConsoleApplicationHarness($app);

        $map = $console->discoverCommands();

        $this->assertSame('App\Console\Commands\DiscoveryCommand', $map['discover:one']);
        $this->assertSame('App\Console\Commands\DiscoveryAliasedCommand', $map['discover:two']);
        $this->assertSame('App\Console\Commands\DiscoveryAliasedCommand', $map['d2']);
        $this->assertSame('App\Console\Commands\DiscoveryAliasedCommand', $map['d3']);
        $this->assertArrayNotHasKey('discover:no-attribute', $map);

        $app->flush();
        $this->removeConsoleBase($base);
    }

    public function testIgnoresTheApplicationPathWhenItIsNotAString(): void
    {
        $base = $this->newConsoleBase();
        $app = new Application($base);
        $app->set('path.command', 123);
        $console = new ConsoleApplicationHarness($app);

        $map = $console->discoverCommands();

        $this->assertArrayNotHasKey('discover:one', $map);

        $app->flush();
        $this->removeConsoleBase($base);
    }

    public function testSkipsApplicationsPathsThatDoNotExist(): void
    {
        $base = $this->newConsoleBase();
        $app = new Application($base);
        $app->set('path.command', $base . DIRECTORY_SEPARATOR . 'missing-dir');
        $console = new ConsoleApplicationHarness($app);

        $map = $console->discoverCommands();

        $this->assertArrayNotHasKey('discover:one', $map);

        $app->flush();
        $this->removeConsoleBase($base);
    }

    public function testAttachesTheCommandLoaderFromACacheFile(): void
    {
        $base = $this->newConsoleBase();
        $cacheDir = $base . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache';
        mkdir($cacheDir, 0777, true);
        file_put_contents(
            $cacheDir . DIRECTORY_SEPARATOR . 'commands.php',
            '<?php return ' . var_export(['demo:hello' => DemoCommand::class], true) . ';'
        );

        try {
            $app = new Application($base);
            $app->set(DemoCommand::class, static fn (): DemoCommand => new DemoCommand());
            $console = new ConsoleApplicationHarness($app);
            $branding = new ConsoleBranding($app, 'Omega Test:', '1.0.0');

            $console->exposeConfigureCommandLoader($branding);

            $this->assertTrue($branding->has('demo:hello'));
        } finally {
            @unlink($cacheDir . DIRECTORY_SEPARATOR . 'commands.php');
            @rmdir($cacheDir);
            $this->removeConsoleBase($base);
        }
    }

    public function testAttachesTheCommandLoaderFromDiscoveredCommands(): void
    {
        require_once __DIR__ . '/Fixtures/DiscoveredCommands/DiscoveryCommand.php';

        $base = $this->newConsoleBase();
        $app = new Application($base);
        $app->set('path.command', __DIR__ . '/Fixtures/DiscoveredCommands');
        $app->set('App\Console\Commands\DiscoveryCommand', static fn () => new \App\Console\Commands\DiscoveryCommand());
        $console = new ConsoleApplicationHarness($app);
        $branding = new ConsoleBranding($app, 'Omega Test:', '1.0.0');

        $console->exposeConfigureCommandLoader($branding);

        $this->assertTrue($branding->has('discover:one'));

        $app->flush();
        $this->removeConsoleBase($base);
    }

    public function testExecutesConsoleRequestsInASubprocess(): void
    {
        $autoload = dirname(__DIR__, 3) . '/vendor/autoload.php';
        $base = $this->newConsoleBase();

        $scenarios = [
            ['input' => null, 'output' => 'new \Symfony\Component\Console\Output\BufferedOutput()', 'shell' => '/bin/bash', 'tokens' => ['pest', '--version']],
            ['input' => ['pest', '--version'], 'output' => 'null', 'shell' => '', 'tokens' => []],
            ['input' => 'array-input', 'output' => 'new \Symfony\Component\Console\Output\BufferedOutput()', 'shell' => '/usr/bin/nologin', 'tokens' => ['pest', '--version']],
            ['input' => null, 'output' => 'new \Symfony\Component\Console\Output\BufferedOutput()', 'shell' => '/usr/bin/zsh', 'tokens' => ['pest', '--version']],
            ['input' => null, 'output' => 'new \Symfony\Component\Console\Output\BufferedOutput()', 'shell' => '/usr/bin/fish', 'tokens' => ['pest', '--version']],
        ];

        foreach ($scenarios as $scenario) {
            $this->assertSame(0, $this->runHandleInSubprocess(
                $autoload,
                $base,
                $scenario['tokens'],
                $scenario['input'],
                $scenario['output'],
                $scenario['shell'],
            ));
        }
    }
}