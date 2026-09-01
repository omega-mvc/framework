<?php

/**
 * Part of Omega - Config Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Config\Facade;

use Omega\Config\ConfigSource as ConfigSourceService;
use Omega\Config\ConfigRepositoryInterface;
use Omega\Config\MergeStrategy;
use Omega\Facade\AbstractFacade;

/**
 * Facade for the ConfigSource service.
 *
 * This facade provides a static, macro-ready interface over the macroable
 * {@see ConfigSourceService} resolved from the application container. Because the
 * underlying service is macroable, macros registered via `ConfigSource::macro()`
 * become callable statically here too.
 *
 * @category   Omega
 * @package    Config
 * @subpackage Facade
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @method static ConfigSourceService fromArray(array $content, ?string $section = null, int $priority = 0)
 * @method static ConfigSourceService fromJson(string $file, ?string $section = null, int $priority = 0)
 * @method static ConfigSourceService fromXml(string $file, ?string $section = null, int $priority = 0)
 * @method static ConfigRepositoryInterface build(?MergeStrategy $strategy = null)
 *
 * @see ConfigSourceService
 */
final class ConfigSource extends AbstractFacade
{
    /**
     * {@inheritdoc}
     */
    public static function getFacadeAccessor(): string
    {
        return ConfigSourceService::class;
    }
}
