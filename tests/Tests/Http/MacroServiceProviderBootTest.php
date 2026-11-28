<?php

declare(strict_types=1);

namespace Tests\Http;

use InvalidArgumentException;
use Omega\Application\Application;
use Omega\Http\MacroServiceProvider;
use Omega\Http\Request;
use Omega\Http\Upload\UploadFile;
use Omega\Validator\Rule\FilterPool;
use Omega\Validator\Rule\ValidPool;
use Omega\Validator\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(MacroServiceProvider::class)]
final class MacroServiceProviderBootTest extends TestCase
{
    protected function tearDown(): void
    {
        Request::resetMacro();

        parent::tearDown();
    }

    public function testRegisterDoesNotRegisterMacros(): void
    {
        Request::resetMacro();

        (new MacroServiceProvider(new Application('')))->register();

        $this->assertFalse(Request::hasMacro('validate'));
        $this->assertFalse(Request::hasMacro('upload'));
    }

    public function testBootRegistersRequestMacros(): void
    {
        $this->bootProvider();

        $this->assertTrue(Request::hasMacro('validate'));
        $this->assertTrue(Request::hasMacro('upload'));
    }

    public function testValidateMacroUsesRequestDataAndClosures(): void
    {
        $this->bootProvider();

        $request = new Request(
            'http://localhost/',
            ['query_1' => 'query'],
            [],
            [],
            [],
            [],
            [],
            'GET',
            '127.0.0.1',
            ''
        );

        $validator = $request->validate(
            fn (ValidPool $rules) => $rules('query_1')->required(),
            fn (FilterPool $filters) => $filters('query_1')->upper_case()
        );

        $this->assertInstanceOf(Validator::class, $validator);
        $this->assertTrue($validator->isValid());
        $this->assertSame('QUERY', $validator->filters->get('query_1'));
    }

    public function testUploadMacroReturnsUploadFileForArrayEntry(): void
    {
        $this->bootProvider();

        $request = new Request(
            '/',
            [],
            [],
            [],
            [],
            [
                'avatar' => [
                    'name'     => 'avatar.jpg',
                    'type'     => 'image/jpeg',
                    'tmp_name' => '/tmp/avatar.jpg',
                    'error'    => 0,
                    'size'     => 1024,
                ],
            ],
            [],
            'POST',
            '127.0.0.1',
            ''
        );

        $upload = $request->upload('avatar');

        $this->assertInstanceOf(UploadFile::class, $upload);
    }

    public function testUploadMacroRejectsMissingFile(): void
    {
        $this->bootProvider();

        $request = new Request('/', [], [], [], [], [], [], 'POST', '127.0.0.1', '');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No uploaded file was found for the name [avatar]');

        $request->upload('avatar');
    }

    public function testUploadMacroRejectsNonArrayFile(): void
    {
        $this->bootProvider();

        // @phpstan-ignore argument.type
        $request = new Request('/', [], [], [], [], ['avatar' => 'not-an-array'], [], 'POST', '127.0.0.1', '');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No uploaded file was found for the name [avatar]');

        $request->upload('avatar');
    }

    private function bootProvider(): void
    {
        Request::resetMacro();

        (new MacroServiceProvider(new Application('')))->boot();
    }
}
