<?php

/**
 * Part of Omega - Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Console\Attribute;

use Attribute;

/**
 * Defines metadata for "make" style commands that generate files.
 *
 * This attribute describes how a file should be generated, including
 * the template to use, destination path, naming conventions, and
 * custom messages for success or warning states.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Attribute
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Make
{
    /**
     * @param string $template Path to the stub/template file
     * @param string $path Container binding or key resolving the base save path
     * @param string $pattern Placeholder in the template to be replaced
     * @param string $suffix Suffix appended to the generated file name
     * @param string $target Logical target path used for display or resolution
     * @param string $info Message shown when generation succeeds
     * @param string $warning Message shown when file already exists
     * @param array $vars Additional template variables [placeholder => transformation]
     */
    public function __construct(
        public string $template,
        public string $path,
        public string $pattern,
        public string $suffix,
        public string $target,
        public string $info,
        public string $warning,
        public array $vars = []
    ) {}
}
