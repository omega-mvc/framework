<?php

declare(strict_types=1);

namespace Tests\Console\Attribute;

use Omega\Console\Attribute\Make;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(Make::class)]
final class MakeTest extends TestCase
{
    public function testStoresTheMakeConfiguration(): void
    {
        $attribute = new Make(
            'stub.php',
            'path.provider',
            '{{CLASS}}',
            'Provider',
            'app/Providers',
            'Created provider.',
            'Provider already exists.',
            ['class' => 'Foo'],
        );

        $this->assertSame('stub.php', $attribute->template);
        $this->assertSame('path.provider', $attribute->path);
        $this->assertSame('{{CLASS}}', $attribute->pattern);
        $this->assertSame('Provider', $attribute->suffix);
        $this->assertSame('app/Providers', $attribute->target);
        $this->assertSame('Created provider.', $attribute->info);
        $this->assertSame('Provider already exists.', $attribute->warning);
        $this->assertSame(['class' => 'Foo'], $attribute->vars);
    }

    public function testDefaultsTheTemplateVariablesToAnEmptyArray(): void
    {
        $attribute = new Make(
            'stub.php',
            'path.provider',
            '{{CLASS}}',
            'Provider',
            'app/Providers',
            'Created provider.',
            'Provider already exists.',
        );

        $this->assertSame([], $attribute->vars);
    }
}
