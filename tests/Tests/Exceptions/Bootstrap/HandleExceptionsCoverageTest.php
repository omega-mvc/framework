<?php

declare(strict_types=1);

namespace Tests\Exceptions\Bootstrap;

use ArrayObject;
use ErrorException;
use Omega\Application\Application;
use Omega\Exceptions\Bootstrapper\HandleExceptions;
use Omega\Exceptions\ExceptionHandler;
use Omega\Http\Request;
use Omega\Http\Response;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LogLevel;
use ReflectionMethod;
use ReflectionProperty;
use Tests\Exceptions\Bootstrap\Fixtures\TestLog;
use Throwable;

use function error_reporting;
use function getenv;
use function ini_get;
use function is_string;
use function ini_set;
use function putenv;
use function restore_error_handler;
use function restore_exception_handler;
use function trigger_error;

/**
 * @internal
 */
#[CoversClass(HandleExceptions::class)]
final class HandleExceptionsCoverageTest extends TestCase
{
    private ?string $env = null;

    private string|false $displayErrors = '';

    protected function setUp(): void
    {
        $env         = getenv('APP_ENV');
        $this->env   = is_string($env) ? $env : null;
        $this->displayErrors = ini_get('display_errors');
    }

    protected function tearDown(): void
    {
        HandleExceptions::resetHandlersState();

        putenv($this->env === null ? 'APP_ENV' : 'APP_ENV=' . $this->env);
        ini_set('display_errors', is_string($this->displayErrors) ? $this->displayErrors : '1');
        error_reporting(E_ALL);
    }

    #[Test]
    public function bootstrapIsIdempotentWhenHandlersAreAlreadyRegistered(): void
    {
        $app = $this->makeApp();

        $handle = new HandleExceptions();
        $handle->bootstrap($app);
        $handle->bootstrap($app);

        $this->assertNotNull(HandleExceptions::$reserveMemory);
    }

    #[Test]
    public function bootstrapRegistersGlobalHandlersOutsideOfTesting(): void
    {
        putenv('APP_ENV=production');

        $app = $this->makeApp();

        try {
            (new HandleExceptions())->bootstrap($app);

            $this->assertSame('Off', ini_get('display_errors'));
        } finally {
            restore_error_handler();
            restore_exception_handler();
        }
    }

    #[Test]
    public function handleErrorReturnsFalseWhenTheLevelIsNotReported(): void
    {
        $app = $this->makeApp();
        $app->set(ExceptionHandler::class, fn () => new ExceptionHandler($app));

        $handle = $this->attach(new HandleExceptions(), $app);

        error_reporting(0);

        $this->assertFalse($handle->handleError(E_WARNING, 'warning', __FILE__, __LINE__));
    }

    #[Test]
    public function handleExceptionRendersTheResponseForNonCliRequests(): void
    {
        $app = $this->makeApp();
        $app->set('request', static fn (): Request => new Request('/'));

        $state = new ArrayObject(['sent' => false]);

        $handler = new class ($app, $state) extends ExceptionHandler {
            /**
             * @param ArrayObject<string, bool> $state
             */
            public function __construct(Application $app, private ArrayObject $state)
            {
                parent::__construct($app);
            }

            public function report(Throwable $th): void
            {
            }

            public function render(Request $request, Throwable $th): Response
            {
                return new class ($this->state) extends Response {
                    /**
                     * @param ArrayObject<string, bool> $state
                     */
                    public function __construct(private ArrayObject $state)
                    {
                        parent::__construct('rendered', 500);
                    }

                    public function send(): Response
                    {
                        $this->state['sent'] = true;

                        return $this;
                    }
                };
            }
        };

        $app->set(ExceptionHandler::class, static fn () => $handler);

        $handle = $this->attach(new class () extends HandleExceptions {
            protected function isCli(): bool
            {
                return false;
            }
        }, $app);

        $handle->handleException(new ErrorException('testing'));

        $this->assertTrue($state['sent']);
    }

    #[Test]
    public function handleShutdownIgnoresNonFatalErrors(): void
    {
        @trigger_error('non fatal', E_USER_WARNING);

        $handle = new HandleExceptions();
        $handle->handleShutdown();

        $this->assertNull(HandleExceptions::$reserveMemory);
    }

    #[Test]
    public function handleShutdownConvertsFatalErrorsIntoExceptions(): void
    {
        $app = $this->makeApp();
        $app->set(ExceptionHandler::class, fn () => new class ($app) extends ExceptionHandler {
            public ?Throwable $reported = null;

            public function report(Throwable $th): void
            {
                $this->reported = $th;
            }
        });

        $handle = $this->attach(new class () extends HandleExceptions {
            /** @return array{type: int, message: string, file: string, line: int} */
            protected function lastError(): array
            {
                return [
                    'type'    => E_ERROR,
                    'message' => 'fatal',
                    'file'    => __FILE__,
                    'line'    => __LINE__,
                ];
            }
        }, $app);

        $handle->handleShutdown();

        $handler = $app->get(ExceptionHandler::class);
        $this->assertInstanceOf(ExceptionHandler::class, $handler);
    }

    #[Test]
    public function theLogMethodReturnsFalseWithoutALogger(): void
    {
        $app = $this->makeApp();

        $handle = $this->attach(new HandleExceptions(), $app);

        $this->assertTrue($handle->handleError(E_USER_DEPRECATED, 'deprecation', __FILE__, __LINE__));
    }

    #[Test]
    public function theLogMethodUsesTheLoggerWhenAvailable(): void
    {
        $log = new TestLog();

        $app = $this->makeApp();
        $app->set('logging', static fn () => $log);

        $handle = $this->attach(new HandleExceptions(), $app);

        $this->assertTrue($handle->handleError(E_USER_DEPRECATED, 'deprecation', __FILE__, __LINE__));
    }

    #[Test]
    public function mapLevelMapsEverySupportedLevel(): void
    {
        $method = new ReflectionMethod(HandleExceptions::class, 'mapLevel');

        $this->assertSame(LogLevel::CRITICAL, $method->invoke(new HandleExceptions(), E_ERROR));
        $this->assertSame(LogLevel::ERROR, $method->invoke(new HandleExceptions(), E_WARNING));
    }

    private function makeApp(): Application
    {
        $app = new Application(basePath: __DIR__ . '/../../../Exceptions/fixtures/application-read/');
        $app->set('environment', 'testing');

        return $app;
    }

    private function attach(HandleExceptions $handle, Application $app): HandleExceptions
    {
        $property = new ReflectionProperty(HandleExceptions::class, 'app');
        $property->setValue($handle, $app);

        return $handle;
    }
}
