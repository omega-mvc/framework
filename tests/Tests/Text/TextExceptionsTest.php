<?php

declare(strict_types=1);

namespace Tests\Text;

use InvalidArgumentException;
use Omega\Text\Exceptions\AbstractTextException;
use Omega\Text\Exceptions\NoReturnException;
use Omega\Text\Exceptions\PropertyNotExistException;
use Omega\Text\Exceptions\TextExceptionInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

use function class_implements;
use function class_parents;

#[CoversClass(AbstractTextException::class)]
#[CoversClass(NoReturnException::class)]
#[CoversClass(PropertyNotExistException::class)]
final class TextExceptionsTest extends TestCase
{
    public function testThrowsAFormattedNoReturnExceptionMessage(): void
    {
        $exception = new NoReturnException('slug', '-~+-');

        $this->assertSame(
            'The method slug called with -~+- did not return anything.',
            $exception->getMessage()
        );
    }

    public function testExtendsTheAbstractTextException(): void
    {
        $this->assertContains(AbstractTextException::class, class_parents(NoReturnException::class));
    }

    public function testIsAnInvalidArgumentException(): void
    {
        $this->assertContains(InvalidArgumentException::class, class_parents(NoReturnException::class));
    }

    public function testImplementsTheTextExceptionInterface(): void
    {
        $this->assertContains(TextExceptionInterface::class, class_implements(NoReturnException::class));
    }

    public function testThrowsAFormattedPropertyNotExistExceptionMessage(): void
    {
        $exception = new PropertyNotExistException('foo');

        $this->assertSame('Property `foo` not exist.', $exception->getMessage());
    }

    public function testIsAProperPropertyException(): void
    {
        $this->assertContains(
            AbstractTextException::class,
            class_parents(PropertyNotExistException::class)
        );
    }
}
