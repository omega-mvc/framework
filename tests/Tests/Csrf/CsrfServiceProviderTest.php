<?php

declare(strict_types=1);

namespace Tests\Csrf;

use Omega\Application\Application;
use Omega\Csrf\Csrf;
use Omega\Csrf\CsrfInterface;
use Omega\Csrf\CsrfServiceProvider;
use Omega\Session\SessionManager;
use Omega\Session\Storage\ArrayStorage;
use Omega\View\Templator\DirectiveTemplator;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use stdClass;
use Tests\TestCase;

use function sprintf;

#[CoversClass(CsrfServiceProvider::class)]
final class CsrfServiceProviderTest extends TestCase
{
    public function testRegistersTheCsrfServices(): void
    {
        $app = $this->makeApp();

        $csrf = $app->get(CsrfInterface::class);

        $this->assertInstanceOf(Csrf::class, $csrf);
        $this->assertSame($csrf, $app->get('csrf'));
    }

    public function testRegistersTheCsrfDirective(): void
    {
        $app = $this->makeApp();

        $csrf = $app->get('csrf');

        $this->assertInstanceOf(Csrf::class, $csrf);

        $output = DirectiveTemplator::call('csrf');

        $this->assertSame(
            sprintf(
                '<input type="hidden" name="%s" value="%s">',
                $csrf->getTokenField(),
                $csrf->getToken()
            ),
            $output
        );
    }

    public function testThrowsWhenTheSessionServiceIsNotAvailable(): void
    {
        $app = $this->makeApp();
        $app->set('session', static fn (): stdClass => new stdClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The session service is not available.');

        $app->get(CsrfInterface::class);
    }

    public function testThrowsWhenTheCsrfServiceIsNotAvailable(): void
    {
        $app = $this->makeApp();
        $app->set(CsrfInterface::class, static fn (): stdClass => new stdClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The csrf service is not available.');

        $app->get('csrf');
    }

    public function testThrowsWhenTheDirectiveCannotResolveTheCsrfService(): void
    {
        $app = $this->makeApp();
        $app->set('csrf', static fn (): stdClass => new stdClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The csrf service is not available.');

        DirectiveTemplator::call('csrf');
    }

    private function makeApp(): Application
    {
        $app = new Application(dirname(__DIR__, 3) . '/');
        $app->set('session', static fn (): SessionManager => new SessionManager('array', new ArrayStorage()));

        (new CsrfServiceProvider($app))->boot();

        return $app;
    }
}
