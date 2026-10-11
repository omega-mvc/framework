<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Exception;
use Omega\Application\Application;
use Omega\Console\Commands\VendorPublishCommand;
use Omega\Container\AbstractServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

#[CoversClass(VendorPublishCommand::class)]
final class VendorPublishCommandTest extends TestCase
{
    private function vendorableDir(string $prefix): string
    {
        $path = sys_get_temp_dir() . '/' . $prefix . '-' . bin2hex(random_bytes(4));
        mkdir($path, 0777, true);

        return $path;
    }

    public function testReportsWhenThereAreNoPublishableResources(): void
    {
        $app = new Application($this->vendorableDir('omegavp'));

        AbstractServiceProvider::flushModule();

        $command = new VendorPublishCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('No publishable resources found.', $result->getDisplay());

        AbstractServiceProvider::flushModule();
        $app->flush();
    }

    public function testPublishesASingleFileModule(): void
    {
        $base = $this->vendorableDir('omegavp');

        mkdir($base . '/vendor', 0777, true);

        $source = $base . '/vendor/config.php';
        $target = $base . '/app/config/config.php';

        file_put_contents($source, '<?php return [\'published\' => true];');
        AbstractServiceProvider::export([$source => $target], 'config');

        $app = new Application($base);

        $command = new VendorPublishCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--tag' => 'config']);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('Publishing resources...', $result->getDisplay());
        $this->assertStringContainsString(
            'Done! 1 resource(s) have been successfully published.',
            $result->getDisplay()
        );
        $this->assertSame('<?php return [\'published\' => true];', file_get_contents($target));

        AbstractServiceProvider::flushModule();
        $app->flush();
    }

    public function testReportsAnErrorWhenTheTagDoesNotMatchAnyModule(): void
    {
        $base = $this->vendorableDir('omegavp');

        mkdir($base . '/vendor', 0777, true);

        $source = $base . '/vendor/config.php';
        $target = $base . '/app/config/config.php';

        file_put_contents($source, 'x');
        AbstractServiceProvider::export([$source => $target], 'config');

        $app = new Application($base);

        $command = new VendorPublishCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--tag' => 'assets']);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'No publishable resources found for tag: assets',
            $result->getDisplay()
        );

        AbstractServiceProvider::flushModule();
        $app->flush();
    }

    public function testPublishesEveryModuleWhenTheTagIsAWildcard(): void
    {
        $base = $this->vendorableDir('omegavp');

        mkdir($base . '/vendor', 0777, true);

        $sourceA = $base . '/vendor/a.txt';
        $sourceB = $base . '/vendor/b.txt';
        $targetA = $base . '/app/a/a.txt';
        $targetB = $base . '/app/b/b.txt';

        file_put_contents($sourceA, 'a');
        file_put_contents($sourceB, 'b');
        AbstractServiceProvider::export([$sourceA => $targetA], 'assets');
        AbstractServiceProvider::export([$sourceB => $targetB], 'config');

        $app = new Application($base);

        $command = new VendorPublishCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString(
            'Done! 2 resource(s) have been successfully published.',
            $result->getDisplay()
        );
        $this->assertFileExists($targetA);
        $this->assertFileExists($targetB);

        AbstractServiceProvider::flushModule();
        $app->flush();
    }

    public function testPublishesADirectoryModuleRecursively(): void
    {
        $base = $this->vendorableDir('omegavp');

        $sourceDir = $base . '/vendor/views';
        $targetDir = $base . '/resources/views';

        mkdir($sourceDir . '/nested', 0777, true);
        file_put_contents($sourceDir . '/welcome.view.php', 'welcome');
        file_put_contents($sourceDir . '/nested/page.view.php', 'page');
        AbstractServiceProvider::export([$sourceDir => $targetDir], 'views');

        $app = new Application($base);

        $command = new VendorPublishCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run(['--tag' => 'views']);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString(
            'Done! 1 resource(s) have been successfully published.',
            $result->getDisplay()
        );
        $this->assertSame('welcome', file_get_contents($targetDir . '/welcome.view.php'));
        $this->assertSame('page', file_get_contents($targetDir . '/nested/page.view.php'));

        AbstractServiceProvider::flushModule();
        $app->flush();
    }

    public function testReportsAFailureWhenAModuleCannotBePublished(): void
    {
        $base = $this->vendorableDir('omegavp');

        $missing = $base . '/vendor/missing.txt';
        $target = $base . '/app/missing.txt';

        AbstractServiceProvider::export([$missing => $target], 'assets');

        $app = new Application($base);

        $command = new VendorPublishCommand();
        $command->app = $app;

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            'Done! 0 resource(s) have been successfully published.',
            $result->getDisplay()
        );
        $this->assertFileDoesNotExist($target);

        AbstractServiceProvider::flushModule();
        $app->flush();
    }

    public function testRefusesToOverwriteAnExistingFileUnlessForced(): void
    {
        $base = $this->vendorableDir('omegavp');

        mkdir($base . '/vendor', 0777, true);

        $source = $base . '/vendor/config.php';
        $target = $base . '/app/config/config.php';

        mkdir(dirname($target), 0777, true);
        file_put_contents($source, 'new');
        file_put_contents($target, 'old');
        AbstractServiceProvider::export([$source => $target], 'config');

        $app = new Application($base);

        $command = new VendorPublishCommand();
        $command->app = $app;

        $tester = new CommandTester($command);

        $thrown = null;

        try {
            $tester->run(['--tag' => 'config']);
        } catch (Exception $exception) {
            $thrown = $exception;
        }

        $this->assertInstanceOf(Exception::class, $thrown);
        $this->assertSame('You do not have permission to overwrite the destination file.', $thrown->getMessage());
        $this->assertSame('old', file_get_contents($target));

        $result = (new CommandTester($command))->run(['--tag' => 'config', '--force' => true]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertSame('new', file_get_contents($target));

        AbstractServiceProvider::flushModule();
        $app->flush();
    }
}
