<?php

declare(strict_types=1);

namespace Tests\Console\Commands;

use Omega\Console\Commands\SeedCommand;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SeedCommand::class)]
final class SeedCommandsTest extends AbstractTestCommand
{
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
    }

    /**
     * Tears down the environment after each test method.
     *
     * This method is called automatically by PHPUnit after each test runs.
     * It is responsible for cleaning up resources, flushing the application
     * state, unsetting properties, and resetting any static or global state
     * to avoid side effects between tests.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        $migration = slash(path: __DIR__ . '/fixtures/seeders/');
        array_map('unlink', glob("{$migration}/*.php"));
    }

    /**
     * @test
     */
    public function testItCanCallMakeCommandSeederWithSuccess(): void
    {
        $makeCommand = new SeedCommand($this->argv('omega make:seed BaseSeeder'));
        ob_start();
        $exit = $makeCommand->make();
        ob_get_clean();

        $this->assertSuccess($exit);

        $file = slash(path: __DIR__ . '/fixtures/seeders/BaseSeeder.php');
        $this->assertTrue(file_exists($file));

        $class = file_get_contents($file);
        $this->assertContain('class BaseSeeder extends AbstractSeeder', $class, 'Stub test');
        $this->assertContain('public function run(): void', $class, 'Stub test');
    }

    /**
     * @test
     */
    public function testItCanCallMakeCommandSeedWithFails(): void
    {
        $makeCommand = new SeedCommand($this->argv('omega make:seed'));
        ob_start();
        $exit = $makeCommand->make();
        ob_get_clean();

        $this->assertFails($exit);
    }

    /**
     * @test
     */
    public function testItCanCallMakeCommandSeedWithFailsFileExist(): void
    {
        app()->set('path.seeder', slash(path: __DIR__ . '/fixtures/database/seeders/'));
        $makeCommand = new SeedCommand($this->argv('omega make:seed BasicSeeder'));
        ob_start();
        $exit = $makeCommand->make();
        ob_get_clean();

        $this->assertFails($exit);
    }

    /**
     * @test
     */
    public function testItCanCallMakeExistCommandSeeder(): void
    {
        app()->set('path.seeder', slash(path: __DIR__ . '/fixtures/database/seeders/'));
        $makeCommand = new SeedCommand($this->argv('omega make:seed ExistSeeder --force'));
        ob_start();
        $exit = $makeCommand->make();
        ob_get_clean();

        $this->assertSuccess($exit);

        $file = slash(path: __DIR__ . '/fixtures/database/seeders/ExistSeeder.php');
        $this->assertTrue(file_exists($file));

        $class = file_get_contents($file);
        $this->assertContain('class ExistSeeder extends AbstractSeeder', $class, 'Stub test');
        $this->assertContain('public function run(): void', $class, 'Stub test');
    }
}
