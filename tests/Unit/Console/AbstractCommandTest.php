<?php

declare(strict_types=1);

namespace Tests\Console;

use Omega\Application\Application;
use Omega\Console\AbstractCommand;
use Omega\Console\ConsoleBranding;
use Omega\Console\Exceptions\InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Console\Fixtures\BadArgumentCountCommand;
use Tests\Console\Fixtures\BadArgumentDescriptionCommand;
use Tests\Console\Fixtures\BadArgumentModeCommand;
use Tests\Console\Fixtures\BadOptionCountCommand;
use Tests\Console\Fixtures\BadOptionDescriptionCommand;
use Tests\Console\Fixtures\BadOptionModeCommand;
use Tests\Console\Fixtures\BadOptionShortcutCommand;
use Tests\Console\Fixtures\BadSuggestedValuesCommand;
use Tests\Console\Fixtures\CallerCommand;
use Tests\Console\Fixtures\DemoCommand;
use Tests\Console\Fixtures\MinimalCommand;
use Tests\Console\Fixtures\NoAttributeCommand;
use Tests\Console\Fixtures\NullReturnCommand;
use Tests\Console\Fixtures\SuggestedArrayCommand;
use Tests\Console\Fixtures\TargetCommand;
use Tests\Console\Fixtures\ThrowingTargetCommand;
use Tests\TestCase;

#[CoversClass(AbstractCommand::class)]
final class AbstractCommandTest extends TestCase
{
    public function testConfiguresTheCommandFromTheAsCommandAttribute(): void
    {
        $command = new DemoCommand();

        $this->assertSame('demo:hello', $command->getName());
        $this->assertSame('Demonstrates a command with arguments and options.', $command->getDescription());
        $this->assertSame(['demo:h'], $command->getAliases());
        $this->assertFalse($command->isHidden());
        $this->assertTrue($command->getDefinition()->hasArgument('name'));
        $this->assertTrue($command->getDefinition()->hasOption('greet'));
        $this->assertSame('World', $command->getDefinition()->getArgument('name')->getDefault());
    }

    public function testAppliesTheHiddenFlagAndEmptyDefinitions(): void
    {
        $command = new MinimalCommand();

        $this->assertSame('demo:minimal', $command->getName());
        $this->assertTrue($command->isHidden());
        $this->assertSame('', $command->getDescription());
        $this->assertSame([], $command->getDefinition()->getArguments());
        $this->assertSame([], $command->getDefinition()->getOptions());
    }

    public function testWorksWithoutAnAsCommandAttribute(): void
    {
        $command = new NoAttributeCommand();

        $result = (new CommandTester($command))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('no-attribute', $result->getDisplay());
    }

    public function testUsesTheDefaultArgumentValueWhenNoneIsProvided(): void
    {
        $result = (new CommandTester(new DemoCommand()))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('hello=World|false', $result->getDisplay());
    }

    public function testUsesTheProvidedArgumentAndOptionValues(): void
    {
        $result = (new CommandTester(new DemoCommand()))->run([
            'name'    => 'Ada',
            '--greet' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('hello=Ada|true', $result->getDisplay());
    }

    public function testReturnsSuccessWhenInvokeReturnsNothing(): void
    {
        $result = (new CommandTester(new NullReturnCommand()))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('null-returned', $result->getDisplay());
    }

    public function testRunsAnotherCommandThroughTheApplication(): void
    {
        $app = new Application('/');
        $console = new ConsoleBranding($app, 'Omega Test:', '1.0.0');
        $console->addCommand(new CallerCommand());
        $console->addCommand(new TargetCommand());

        $result = (new CommandTester($console->find('demo:caller')))->run([]);

        $this->assertSame(Command::SUCCESS, $result->statusCode);
        $this->assertStringContainsString('caller-run', $result->getDisplay());
        $this->assertStringContainsString('target-run:World', $result->getDisplay());

        $app->flush();
    }

    public function testReportsAFailureWhenTheCalledCommandThrows(): void
    {
        $app = new Application('/');
        $console = new ConsoleBranding($app, 'Omega Test:', '1.0.0');
        $console->addCommand(new CallerCommand());
        $console->addCommand(new ThrowingTargetCommand());

        $result = (new CommandTester($console->find('demo:caller')))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString(
            "Unable to execute command 'demo:target': boom",
            $result->getDisplay()
        );

        $app->flush();
    }

    public function testReportsAFailureWhenTheCommandHasNoApplication(): void
    {
        $result = (new CommandTester(new CallerCommand()))->run([]);

        $this->assertSame(Command::FAILURE, $result->statusCode);
        $this->assertStringContainsString('The application instance is not available.', $result->getDisplay());
    }

    public function testSupportsOptionSuggestedValuesAsAnArray(): void
    {
        $command = new SuggestedArrayCommand();

        $this->assertTrue($command->getDefinition()->getOption('sug')->hasCompletion());
    }

    public function testThrowsWhenAnArgumentConfigurationHasTooFewElements(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BadArgumentCountCommand();
    }

    public function testThrowsWhenAnOptionConfigurationHasTooFewElements(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BadOptionCountCommand();
    }

    public function testThrowsWhenAnArgumentModeIsNotAnInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BadArgumentModeCommand();
    }

    public function testThrowsWhenAnArgumentDescriptionIsNotAString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BadArgumentDescriptionCommand();
    }

    public function testThrowsWhenAnOptionModeIsNotAnInteger(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BadOptionModeCommand();
    }

    public function testThrowsWhenAnOptionDescriptionIsNotAString(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BadOptionDescriptionCommand();
    }

    public function testThrowsWhenAnOptionShortcutIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BadOptionShortcutCommand();
    }

    public function testThrowsWhenSuggestedValuesAreNeitherAnArrayNorAClosure(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new BadSuggestedValuesCommand();
    }
}
