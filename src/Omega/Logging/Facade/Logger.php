<?php

/**
 * Part of Omega - Logging Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

declare(strict_types=1);

namespace Omega\Logging\Facade;

use Closure;
use Omega\Facade\AbstractFacade;
use Omega\Logging\LoggingManager;
use Psr\Log\LoggerInterface;
use Stringable;

/**
 * Facade for the Logger service.
 *
 * This facade provides a static interface to the underlying {@see LoggingManager}
 * resolved from the application container. It allows convenient static-style
 * calls while still relying on dependency injection and the container under the hood.
 *
 * Usage of this facade does not create a global state; the underlying instance
 * is still managed by the container and may be swapped, mocked, or replaced
 * for testing or customization purposes.
 *
 * @category   Omega
 * @package    Logging
 * @subpackage Facade
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @method static LoggingManager            setDriver(string $driverName, Closure|LoggerInterface $driver)
 * @method static LoggingManager            setDefaultDriver(LoggerInterface $driver)
 * @method static LoggerInterface           getDriver(?string $driverName = null)
 * @method static void                      emergency(string|Stringable $message, array $context = [])
 * @method static void                      alert(string|Stringable $message, array $context = [])
 * @method static void                      critical(string|Stringable $message, array $context = [])
 * @method static void                      error(string|Stringable $message, array $context = [])
 * @method static void                      warning(string|Stringable $message, array $context = [])
 * @method static void                      notice(string|Stringable $message, array $context = [])
 * @method static void                      info(string|Stringable $message, array $context = [])
 * @method static void                      debug(string|Stringable $message, array $context = [])
 * @method static void                      log(mixed $level, string|Stringable $message, array $context = [])
 * @method static void                      setLogToStdOut(string $stdOutPath)
 * @method static void                      setLogFilePath(string $logDirectory)
 * @method static void                      setFileHandle(string $writeMode)
 * @method static void                      setDateFormat(string $dateFormat)
 * @method static void                      setLogLevelThreshold(string $logLevelThreshold)
 * @method static void                      write(string $message)
 * @method static string                    getLogFilePath()
 * @method static string                    getLastLogLine()
 *
 * @see \Omega\Logging\LoggingManager
 */
final class Logger extends AbstractFacade
{
    /**
     * {@inheritdoc}
     */
    public static function getFacadeAccessor(): string
    {
        return 'logging';
    }
}
