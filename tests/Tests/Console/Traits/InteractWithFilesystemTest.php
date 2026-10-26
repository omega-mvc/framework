<?php

declare(strict_types=1);

namespace Tests\Console\Traits;

use Omega\Console\Traits\InteractWithFilesystemTrait;
use Tests\Console\Fixtures\FilesystemProbe;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\AfterClass;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\BeforeClass;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

final class InteractWithFilesystemTest extends TestCase
{
    public function testReturnsAnEmptyListWhenTheDirectoryDoesNotExist(): void
    {
        $probe = new FilesystemProbe();

        $this->assertSame([], $probe->find('/no/such/directory', '*'));
    }

    public function testMatchesFilesByASinglePattern(): void
    {
        $probe = new FilesystemProbe();
        $files = $probe->find(__DIR__ . '/../Fixtures/Files', '*.txt');

        $this->assertCount(1, $files);
        $this->assertStringEndsWith('a.txt', $files[0]);
    }

    public function testMatchesFilesByMultiplePatterns(): void
    {
        $probe = new FilesystemProbe();
        $files = $probe->find(__DIR__ . '/../Fixtures/Files', ['*.php', '*.log']);

        $this->assertCount(2, $files);
    }

    public function testMatchesEveryFileWithTheDefaultPattern(): void
    {
        $probe = new FilesystemProbe();
        $files = $probe->find(__DIR__ . '/../Fixtures/Files');

        $this->assertCount(3, $files);
    }

    public function testExcludesFilesMatchingTheGivenPatterns(): void
    {
        $probe = new FilesystemProbe();
        $files = $probe->find(__DIR__ . '/../Fixtures/Files', '*', ['*.log']);

        $this->assertCount(2, $files);
        $this->assertNotContains(__DIR__ . '/../Fixtures/Files/b.log', $files);
    }

    public function testSkipsHiddenFiles(): void
    {
        $probe = new FilesystemProbe();
        $directory = $this->makeTemporaryDirectory();

        file_put_contents($directory . '/.hidden.php', '<?php');
        file_put_contents($directory . '/visible.php', '<?php');

        $files = $probe->find($directory, '*.php');

        $this->assertCount(1, $files);
        $this->assertStringEndsWith('visible.php', $files[0]);
    }

    public function testSkipsSymbolicLinks(): void
    {
        $probe = new FilesystemProbe();
        $directory = $this->makeTemporaryDirectory();

        file_put_contents($directory . '/target.php', '<?php');
        symlink($directory . '/target.php', $directory . '/link.php');

        $files = $probe->find($directory, '*.php');

        $this->assertCount(1, $files);
        $this->assertStringEndsWith('target.php', $files[0]);
    }

    public function testSearchesNestedDirectories(): void
    {
        $probe = new FilesystemProbe();
        $directory = $this->makeTemporaryDirectory();

        mkdir($directory . '/nested');
        file_put_contents($directory . '/root.php', '<?php');
        file_put_contents($directory . '/nested/child.php', '<?php');

        $files = $probe->find($directory, '*.php');

        $this->assertCount(2, $files);
        $this->assertStringEndsWith('nested/child.php', $files[0]);
        $this->assertStringEndsWith('root.php', $files[1]);
    }

    private function makeTemporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/omega-files-' . bin2hex(random_bytes(4));

        mkdir($directory, 0777, true);

        $this->temporaryDirectories[] = $directory;

        return $directory;
    }

    /**
     * @var list<string>
     */
    private array $temporaryDirectories = [];

    #[After]
    protected function removeTemporaryDirectories(): void
    {
        foreach ($this->temporaryDirectories as $directory) {
            foreach (glob($directory . '/{,.}*', GLOB_BRACE) ?: [] as $file) {
                if (is_file($file) || is_link($file)) {
                    unlink($file);
                }
            }

            foreach (glob($directory . '/*', GLOB_ONLYDIR) ?: [] as $subdirectory) {
                foreach (glob($subdirectory . '/{,.}*', GLOB_BRACE) ?: [] as $file) {
                    if (is_file($file) || is_link($file)) {
                        unlink($file);
                    }
                }

                @rmdir($subdirectory);
            }

            @rmdir($directory);
        }

        $this->temporaryDirectories = [];
    }
}
