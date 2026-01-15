<?php

declare(strict_types=1);


namespace Tests\Config\Source;

use Omega\Config\Source\OptionsConfig;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use phpmock\phpunit\PHPMock;

#[CoversClass(OptionsConfig::class)]
class OptionsTest extends TestCase
{
    use PHPMock;

    public function testShouldReturnValues(): void
    {
        $option = 'test';
        $value = ['key' => 'value'];
        $source = new OptionsConfig($option);
        $getOption = $this->getFunctionMock(__NAMESPACE__, 'get_option');

        $getOption->expects($this->once())
            ->with($option, [])
            ->willReturn($value);

        $this->assertEquals($value, $source->fetch());
    }
}
