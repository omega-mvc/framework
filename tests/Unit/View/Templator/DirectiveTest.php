<?php

declare(strict_types=1);

namespace Tests\View\Templator;

use Omega\View\Exceptions\DirectiveCanNotBeRegisterException;
use Omega\View\Exceptions\DirectiveNotRegisterException;
use Omega\View\Templator;
use Omega\View\Templator\DirectiveTemplator;
use Omega\View\TemplatorFinder;
use PHPUnit\Framework\Attributes\CoversClass;
use RuntimeException;
use Stringable;
use Tests\TestCase;

#[CoversClass(DirectiveCanNotBeRegisterException::class)]
#[CoversClass(DirectiveNotRegisterException::class)]
#[CoversClass(Templator::class)]
#[CoversClass(DirectiveTemplator::class)]
#[CoversClass(TemplatorFinder::class)]
final class DirectiveTest extends TestCase
{
    private Templator $templator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templator = new Templator(
            new TemplatorFinder([__DIR__ . '/../fixtures/view/templator/view/'], ['']),
            __DIR__ . '/../fixtures/view/templator/'
        );
    }

    public function testCanRenderEachBreak(): void
    {
        DirectiveTemplator::register('sum', fn (int $a, int $b): int => $a + $b);

        $out = $this->templator->templates('<html><head></head><body>{% sum(1, 2) %}</body></html>');

        $this->assertEquals(
            "<html><head></head><body>"
            . "<?php echo Omega\View\Templator\DirectiveTemplator::call('sum', 1, 2); ?>"
            . "</body></html>",
            $out
        );
    }

    public function testThrowExceptionDueDirectiveNotRegister(): void
    {
        $this->expectException(DirectiveNotRegisterException::class);

        DirectiveTemplator::call('unknow', 0);
    }

    public function testCanNotRegisterDirective(): void
    {
        $this->expectException(DirectiveCanNotBeRegisterException::class);

        DirectiveTemplator::register('include', fn (string $file): string => $file);
    }

    public function testCanRegisterAndCallDirective(): void
    {
        DirectiveTemplator::register('sum', fn (int $a, int $b): int => $a + $b);

        $this->assertEquals(2, DirectiveTemplator::call('sum', 1, 1));
    }

    public function testSkipsReservedDirectiveInParse(): void
    {
        $finder    = new TemplatorFinder([]);
        $directive = new DirectiveTemplator($finder, '/tmp');

        $template = '{% include("file") %}';
        $out      = $directive->parse($template);

        $this->assertEquals('{% include("file") %}', $out);
    }

    public function testCanResetRegisteredDirectives(): void
    {
        $this->expectException(DirectiveNotRegisterException::class);

        DirectiveTemplator::register('sum', fn (int $a, int $b): int => $a + $b);

        DirectiveTemplator::reset();
        DirectiveTemplator::call('sum', 1, 1);
    }

    public function testCallReturnsStringResultDirectly(): void
    {
        DirectiveTemplator::register('stringify', fn (): string => 'converted');

        $this->assertSame('converted', DirectiveTemplator::call('stringify'));
    }

    public function testCallThrowsWhenDirectiveReturnsANonScalarValue(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('must return a string or scalar value');

        DirectiveTemplator::register('arrayify', fn (): array => []);

        DirectiveTemplator::call('arrayify');
    }

    public function testCallCastsAFloatResultToString(): void
    {
        DirectiveTemplator::register('floatify', fn (): float => 1.5);

        $this->assertSame('1.5', DirectiveTemplator::call('floatify'));
    }

    public function testCallCastsABooleanResultToString(): void
    {
        DirectiveTemplator::register('boolify', fn (): bool => true);

        $this->assertSame('1', DirectiveTemplator::call('boolify'));
    }

    public function testCallCastsAStringableResultToString(): void
    {
        $stringable = new class () implements Stringable {
            public function __toString(): string
            {
                return 'stringable';
            }
        };

        DirectiveTemplator::register('stringableify', fn (): Stringable => $stringable);

        $this->assertSame('stringable', DirectiveTemplator::call('stringableify'));
    }
}
