<?php

declare(strict_types=1);

namespace Tests\Console;

use Omega\Application\Application;
use Omega\Console\CommandLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Tests\Console\Fixtures\DemoCommand;
use Tests\Console\Fixtures\PlainCommand;
use Tests\TestCase;

#[CoversClass(CommandLoader::class)]
final class CommandLoaderTest extends TestCase
{
    public function testReturnsAnAbstractCommandInstanceWithTheApplicationInjected(): void
    {
        $app = new Application('/');
        $app->set(DemoCommand::class, static fn (): DemoCommand => new DemoCommand());
        $loader = new CommandLoader($app, ['demo:hello' => DemoCommand::class]);

        $command = $loader->get('demo:hello');

        $this->assertInstanceOf(DemoCommand::class, $command);

        $property = new ReflectionProperty(DemoCommand::class, 'app');

        $this->assertSame($app, $property->getValue($command));

        $app->flush();
    }

    public function testReturnsAPlainSymfonyCommandWithoutInjectingTheApplication(): void
    {
        $app = new Application('/');
        $app->set(PlainCommand::class, static fn (): PlainCommand => new PlainCommand());
        $loader = new CommandLoader($app, ['plain:run' => PlainCommand::class]);

        $command = $loader->get('plain:run');

        $this->assertInstanceOf(PlainCommand::class, $command);

        $app->flush();
    }

    public function testReportsWhetherACommandNameIsDefined(): void
    {
        $loader = new CommandLoader(new Application('/'), ['demo:hello' => DemoCommand::class]);

        $this->assertTrue($loader->has('demo:hello'));
        $this->assertFalse($loader->has('unknown:command'));
    }

    public function testReturnsAllDefinedCommandNames(): void
    {
        $loader = new CommandLoader(new Application('/'), [
            'demo:hello' => DemoCommand::class,
            'plain:run' => PlainCommand::class,
        ]);

        $this->assertSame(['demo:hello', 'plain:run'], $loader->getNames());
    }

    public function testThrowsWhenTheCommandIsNotDefined(): void
    {
        $loader = new CommandLoader(new Application('/'), []);

        $this->expectException(CommandNotFoundException::class);
        $this->expectExceptionMessage('Command "missing:command" is not defined.');

        $loader->get('missing:command');
    }
}
