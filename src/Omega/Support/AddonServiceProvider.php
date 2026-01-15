<?php

/**
 * Part of Omega - Facades Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Support;

use Closure;
use Omega\Container\Provider\AbstractServiceProvider;
use Omega\Http\Request;
use Omega\Http\Upload\UploadFile;
use Omega\Validator\Validator;

/**
 * Service provider that registers additional macros to extend the Request functionality.
 *
 * This provider adds two macros to the Request object:
 * 1. `validate` - allows inline validation using closures for rules and filters.
 * 2. `upload` - simplifies retrieving uploaded files as `UploadFile` instances.
 *
 * Typically used to enhance request handling with reusable utility methods.
 *
 * @category  Omega
 * @package   Support
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class AddonServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritDoc}
     */
    public function register(): void
    {
        Request::macro(
            'validate',
            fn (?Closure $rule = null, ?Closure $filter = null) => Validator::make($this->{'all'}(), $rule, $filter)
        );

        Request::macro(
            'upload',
            function ($file_name) {
                $files = $this->{'getFile'}();

                return new UploadFile($files[$file_name]);
            }
        );
    }
}
