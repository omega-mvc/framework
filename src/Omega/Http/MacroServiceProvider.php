<?php

declare(strict_types=1);

namespace Omega\Http;

use Closure;
use InvalidArgumentException;
use Omega\Container\AbstractServiceProvider;
use Omega\Http\Upload\UploadFile;
use Omega\Validator\Validator;

use function is_array;
use function sprintf;

class MacroServiceProvider extends AbstractServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        Request::macro(
            'validate',
            function (?Closure $rule = null, ?Closure $filter = null) {
                return Validator::make(
                    $this->all(),
                    $rule,
                    $filter
                );
            }
        );

        Request::macro(
            'upload',
            function (string $fileName) {
                $files = $this->getFile();

                $file = $files[$fileName] ?? null;

                if (!is_array($file)) {
                    throw new InvalidArgumentException(sprintf('No uploaded file was found for the name [%s]', $fileName));
                }

                return new UploadFile([
                    'name'     => $file['name'],
                    'type'     => $file['type'],
                    'tmp_name' => $file['tmp_name'],
                    'error'    => $file['error'],
                    'size'     => $file['size'],
                ]);
            }
        );
    }
}