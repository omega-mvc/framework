<?php

declare(strict_types=1);

namespace Tests\Exceptions;

use Omega\Application\Application;
use Omega\Event\Dispatcher\Dispatcher;
use Omega\Event\Dispatcher\DispatcherInterface;
use Omega\Event\EventInterface;
use Omega\Event\Events\ExceptionEvent;
use Omega\Exceptions\ExceptionHandler;
use Omega\Http\Exceptions\HttpException;
use Omega\Http\Exceptions\HttpResponseException;
use Omega\Http\Request;
use Omega\Http\Response;
use Omega\View\Templator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use RuntimeException;
use Throwable;

use function is_array;
use function is_string;

/**
 * @internal
 */
#[CoversClass(ExceptionHandler::class)]
final class ExceptionHandlerCoverageTest extends TestCase
{
    private const VIEW_CONTENT = "<h1>Too Many Request</h1>\n";

    private Application $app;

    private ExceptionHandler $handler;

    protected function setUp(): void
    {
        $this->app = new Application(__DIR__ . '/fixtures/application-read/');
        $this->app->set('environment', 'testing');
        $this->app->set('app.debug', false);

        $this->handler = new ExceptionHandler($this->app);
    }

    protected function tearDown(): void
    {
        $this->app->flush();
    }

    #[Test]
    public function rendersAJsonResponseForAGenericThrowable(): void
    {
        $response = $this->handler->render($this->jsonRequest(), new RuntimeException('boom'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame([
            'code'     => 500,
            'messages' => ['message' => 'Internal Server Error'],
        ], $response->getContent());
    }

    #[Test]
    public function rendersADebugJsonResponseForAnHttpException(): void
    {
        $this->app->set('app.debug', true);

        $response = $this->handler->render($this->jsonRequest(), new HttpException(404, 'Not Found'));

        $content = $response->getContent();
        $this->assertIsArray($content);
        $this->assertSame(404, $response->getStatusCode());

        $messages = $content['messages'];
        $this->assertIsArray($messages);
        $this->assertSame('Not Found', $messages['message']);
        $this->assertSame(HttpException::class, $messages['exception']);
    }

    #[Test]
    public function returnsTheResponseCarriedByAnHttpResponseException(): void
    {
        $response = $this->handler->render(
            new Request('/test'),
            new HttpResponseException(new Response('direct', 202))
        );

        $this->assertSame('direct', $response->getContent());
        $this->assertSame(202, $response->getStatusCode());
    }

    #[Test]
    public function rendersAGenericThrowableOutsideOfProduction(): void
    {
        $response = $this->handler->render(new Request('/test'), new RuntimeException('boom'));

        $this->assertSame('boom', $response->getContent());
        $this->assertSame(500, $response->getStatusCode());
    }

    #[Test]
    public function rendersTheProductionViewForAGenericThrowable(): void
    {
        $this->app->set('environment', 'prod');
        $this->bindViews();

        $response = $this->handler->render(new Request('/test'), new RuntimeException('boom'));

        $this->assertSame(self::VIEW_CONTENT, $response->getContent());
        $this->assertSame(500, $response->getStatusCode());
    }

    #[Test]
    public function rethrowsTheExceptionInDebugMode(): void
    {
        $this->app->set('app.debug', true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('boom');

        $this->handler->render(new Request('/test'), new RuntimeException('boom'));
    }

    #[Test]
    public function doesNotReportInternalHttpExceptions(): void
    {
        $logger = $this->logger();
        $this->app->set('logging', static fn () => $logger);
        $this->app->set(DispatcherInterface::class, fn () => new Dispatcher());

        $this->handler->report(new HttpException(500, 'Server Error'));

        $this->assertSame([], $logger->records);
    }

    #[Test]
    public function reportsAnExceptionWithoutALoggerBinding(): void
    {
        $logger = $this->logger();

        $this->handler->report(new RuntimeException('boom'));

        $this->assertSame([], $logger->records);
    }

    #[Test]
    public function logsAnExceptionAndDispatchesTheEvent(): void
    {
        $dispatcher = new Dispatcher();
        $dispatched = [];

        $dispatcher->addListener(
            'exception.logged',
            static function (EventInterface $event) use (&$dispatched): void {
                $dispatched[] = $event;
            }
        );

        $logger = $this->logger();
        $this->app->set('logging', static fn () => $logger);
        $this->app->set(DispatcherInterface::class, static fn () => $dispatcher);

        $this->handler->report(new RuntimeException('boom'));

        $this->assertCount(1, $logger->records);
        $this->assertSame(LogLevel::ERROR, $logger->records[0]['level']);
        $this->assertSame('boom', $logger->records[0]['message']);
        $this->assertSame(RuntimeException::class, $logger->records[0]['context']['exception']);
        $this->assertCount(1, $dispatched);
    }

    #[Test]
    public function logsHttpExceptionsAtCriticalLevelWhenLoggedDirectly(): void
    {
        $logger = $this->logger();
        $this->app->set('logging', static fn () => $logger);

        $handler = new class ($this->app) extends ExceptionHandler {
            public function logIt(Throwable $th): void
            {
                $this->log($th);
            }
        };

        $handler->logIt(new HttpException(503, 'Unavailable'));

        $this->assertCount(1, $logger->records);
        $this->assertSame(LogLevel::CRITICAL, $logger->records[0]['level']);
    }

    #[Test]
    public function rendersAnHttpExceptionThroughAnExistingView(): void
    {
        $this->bindViews();

        $response = $this->handler->render(new Request('/test'), new HttpException(500, 'Server Error'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(self::VIEW_CONTENT, $response->getContent());
    }

    #[Test]
    public function fallsBackToTheGenericViewWhenTheStatusViewIsMissing(): void
    {
        $this->bindViews();

        $response = $this->handler->render(new Request('/test'), new HttpException(404, 'Not Found'));

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame(self::VIEW_CONTENT, $response->getContent());
    }

    #[Test]
    public function registersTheViewPathWhenTheConfiguredPathsAreNotAnArray(): void
    {
        $this->app->set('paths.view', 'not-an-array');
        $this->app->set('path.view', '');
        $this->bindFinder();

        $this->app->set(
            'view.instance',
            static fn (TemplatorFinder $finder): Templator => new Templator($finder, '')
        );

        $templator = $this->handler->registerViewPath();

        $this->assertInstanceOf(Templator::class, $templator);
    }

    private function jsonRequest(): Request
    {
        return new Request('/test', [], [], [], [], [], ['content-type' => 'application/json']);
    }

    /**
     * Create an in-memory PSR-3 logger.
     *
     * @return AbstractLogger&object{records: list<array{
     *     level: mixed,
     *     message: string,
     *     context: array<array-key, mixed>
     * }>}
     */
    private function logger(): AbstractLogger
    {
        return new class () extends AbstractLogger {
            /** @var list<array{level: mixed, message: string, context: array<array-key, mixed>}> */
            public array $records = [];

            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = [
                    'level'   => $level,
                    'message' => (string) $message,
                    'context' => $context,
                ];
            }
        };
    }

    private function bindFinder(): void
    {
        $path = __DIR__ . '/fixtures/exceptions/';

        $this->app->set(
            TemplatorFinder::class,
            static fn (): TemplatorFinder => new TemplatorFinder([$path], ['.php'])
        );
    }

    private function bindViews(): void
    {
        $path = __DIR__ . '/fixtures/exceptions/';

        $this->app->set('paths.view', [$path]);
        $this->app->set('path.view', $path);
        $this->bindFinder();

        $this->app->set(
            'view.instance',
            static fn (TemplatorFinder $finder): Templator => new Templator($finder, $path)
        );

        $this->app->set(
            'view.response',
            static fn (): callable => static function (string $view, array $data = []) use ($path): Response {
                $content = file_get_contents($path . $view . '.php');

                return new Response(is_string($content) ? $content : '');
            }
        );
    }
}
