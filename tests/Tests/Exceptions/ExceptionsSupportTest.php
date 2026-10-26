<?php

declare(strict_types=1);

namespace Tests\Exceptions;

use Omega\Exceptions\ApplicationNotAvailableException;
use Omega\Exceptions\WhoopsServiceProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

#[CoversClass(ApplicationNotAvailableException::class)]
#[CoversClass(WhoopsServiceProvider::class)]
final class ExceptionsSupportTest extends TestCase
{
    #[Test]
    public function theApplicationNotAvailableExceptionCarriesItsFixedMessage(): void
    {
        $exception = new ApplicationNotAvailableException();

        $this->assertSame('Application not start yet!', $exception->getMessage());
        $this->assertInstanceOf(RuntimeException::class, $exception);
    }

    #[Test]
    public function whoopsServicesAreRegisteredInDebugMode(): void
    {
        $app = new \Omega\Application\Application(__DIR__ . '/fixtures/application-read/');
        $app->set('app.debug', true);

        (new WhoopsServiceProvider($app))->boot();

        $this->assertInstanceOf(Run::class, $app->get('error.handle'));
        $this->assertInstanceOf(PrettyPageHandler::class, $app->get('error.PrettyPageHandler'));
        $this->assertInstanceOf(PlainTextHandler::class, $app->get('error.PlainTextHandler'));

        $app->flush();
    }

    #[Test]
    public function whoopsServicesAreSkippedOutsideOfDebugMode(): void
    {
        $app = new \Omega\Application\Application(__DIR__ . '/fixtures/application-read/');
        $app->set('app.debug', false);

        (new WhoopsServiceProvider($app))->boot();

        $this->assertFalse($app->bound('error.handle'));
        $this->assertFalse($app->bound('error.PrettyPageHandler'));
        $this->assertFalse($app->bound('error.PlainTextHandler'));

        $app->flush();
    }
}
