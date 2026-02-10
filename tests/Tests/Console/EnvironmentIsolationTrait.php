<?php

/**
 * Part of Omega - Tests\Console Package.
 *
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

namespace Tests\Console;

use PHPUnit\Framework\Attributes\CoversNothing;
use function getenv;
use function putenv;

/**
 * Provides utilities to isolate and control environment variables during tests.
 *
 * This trait allows test cases to safely back up, clear, modify, and restore
 * environment variables without leaking state between tests. It ensures both
 * `$_SERVER` and system-level environment variables accessed via `getenv()`
 * remain synchronized and can be reliably asserted.
 *
 * @category  Tests
 * @package   Console
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
#[CoversNothing]
trait EnvironmentIsolationTrait
{
    /** @var array<string, string> Backup of environment variables retrieved via getenv(). */
    private array $originalEnvBackup = [];

    /** @var array<string, string> Backup of environment variables stored in the $_SERVER superglobal. */
    private array $originalServerBackup = [];

    /**
     * Returns the list of environment variables that must be isolated during tests.
     *
     * These variables are backed up, cleared, and restored to ensure predictable
     * test behavior and prevent side effects between test cases.
     *
     * @return string[]
     */
    protected function getEnvironmentVariables(): array
    {
        return [
            'NO_COLOR',
            'TERM_PROGRAM',
            'COLORTERM',
            'ANSICON',
            'ConEmuANSI',
            'TERM',
            'MSYSTEM',
        ];
    }

    /**
     * Stores the current state of configured environment variables.
     *
     * This method saves values from both `$_SERVER` and `getenv()` so they can
     * later be restored to their original state after the test completes.
     *
     * @return void
     */
    protected function backupEnvironment(): void
    {
        $envVars = $this->getEnvironmentVariables();

        foreach ($envVars as $var) {
            if (isset($_SERVER[$var])) {
                $this->originalServerBackup[$var] = $_SERVER[$var];
            }
        }

        foreach ($envVars as $var) {
            $value = getenv($var);
            if ($value !== false) {
                $this->originalEnvBackup[$var] = $value;
            }
        }
    }

    /**
     * Removes all configured environment variables.
     *
     * This method clears both `$_SERVER` and system environment variables to
     * guarantee a clean and deterministic test environment.
     *
     * @return void
     */
    protected function clearEnvironment(): void
    {
        $envVars = $this->getEnvironmentVariables();

        foreach ($envVars as $var) {
            unset($_SERVER[$var]);
        }

        foreach ($envVars as $var) {
            putenv("{$var}=");
        }
    }

    /**
     * Restores environment variables to their previously backed up state.
     *
     * This method first clears the current environment and then reinstates
     * all values captured by {@see backupEnvironment()}.
     *
     * @return void
     */
    protected function restoreEnvironment(): void
    {
        $this->clearEnvironment();

        foreach ($this->originalServerBackup as $var => $value) {
            $_SERVER[$var] = $value;
        }

        foreach ($this->originalEnvBackup as $var => $value) {
            putenv("{$var}={$value}");
        }

        $this->originalEnvBackup    = [];
        $this->originalServerBackup = [];
    }

    /**
     * Sets a test environment variable.
     *
     * The variable is assigned to both `$_SERVER` and system environment variables
     * to ensure consistent access regardless of the retrieval method.
     *
     * @param string $key   Environment variable name.
     * @param string $value Environment variable value.
     * @return void
     */
    protected function setTestEnvironment(string $key, string $value): void
    {
        $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }

    /**
     * Sets multiple test environment variables at once.
     *
     * @param array<string, string> $variables Key-value pairs of environment variables.
     * @return void
     */
    protected function setTestEnvironments(array $variables): void
    {
        foreach ($variables as $key => $value) {
            $this->setTestEnvironment($key, $value);
        }
    }

    /**
     * Asserts that an environment variable matches the expected value.
     *
     * The assertion verifies both `$_SERVER` and `getenv()` to guarantee
     * environment consistency.
     *
     * @param string $expected Expected value.
     * @param string $variable Environment variable name.
     * @param string $message  Optional assertion message.
     * @return void
     */
    protected function assertEnvironmentEquals(string $expected, string $variable, string $message = ''): void
    {
        $serverValue = $_SERVER[$variable] ?? null;
        $envValue    = getenv($variable);

        if ($message === '') {
            $message = "Environment variable {$variable} should equal '{$expected}'";
        }

        $this->assertEquals($expected, $serverValue, "{$message} (in \$_SERVER)");
        $this->assertEquals($expected, $envValue, "{$message} (via getenv)");
    }

    /**
     * Asserts that an environment variable is not defined.
     *
     * The assertion verifies both `$_SERVER` and `getenv()` to ensure the
     * variable is completely unset.
     *
     * @param string $variable Environment variable name.
     * @param string $message  Optional assertion message.
     * @return void
     */
    protected function assertEnvironmentNotSet(string $variable, string $message = ''): void
    {
        $serverSet = isset($_SERVER[$variable]);
        $envValue  = getenv($variable);

        if ($message === '') {
            $message = "Environment variable {$variable} should not be set";
        }

        $this->assertFalse($serverSet, "{$message} (in \$_SERVER)");
        $this->assertFalse($envValue, "{$message} (via getenv)");
    }
}
