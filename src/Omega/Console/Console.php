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

namespace Omega\Console;

use Omega\Application\Application;
use Omega\Console\Style\Style;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Support\Bootstrap\BootProviders;
use Omega\Support\Bootstrap\ConfigProviders;
use Omega\Support\Bootstrap\RegisterFacades;
use Omega\Support\Bootstrap\RegisterProviders;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;

use function array_fill;
use function array_merge;
use function arsort;
use function explode;
use function floor;
use function is_int;
use function max;
use function min;
use function strlen;
use function strtolower;

/**
 * The Console kernel orchestrates the execution of console commands.
 *
 * It is responsible for:
 * - Bootstrapping the application (configuration, facades, providers).
 * - Handling input arguments passed via the CLI.
 * - Resolving and executing registered command classes.
 * - Providing suggestions for mistyped commands using Jaro-Winkler similarity.
 * - Managing the application exit status.
 *
 * This class acts as the entry point for the Omega console runtime.
 *
 * @category  Omega
 * @package   Console
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class Console
{
    /**
     * The application container instance.
     *
     * Provides dependency injection, configuration, and service resolution.
     */
    protected Application $app;

    /**
     * The last exit status code returned by a command execution.
     *
     * Typically:
     * - 0 for success
     * - 1 for failure or unknown command
     */
    protected int $exitCode;

    /** @var array<int, class-string> The list of bootstrapper classes to run during initialization. */
    protected array $bootstrappers = [
        ConfigProviders::class,
        RegisterFacades::class,
        RegisterProviders::class,
        BootProviders::class,
    ];

    /**
     * Create a new Console instance.
     *
     * @param Application $app The application container.
     * @return void
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Handle a console request using raw CLI arguments.
     *
     * Steps:
     * - Bootstrap the application.
     * - Search for a matching registered command.
     * - Execute the command if found.
     * - Suggest similar commands if no match exists.
     *
     * @param string|array<int, string> $arguments CLI arguments
     * @return int Exit code
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function handle(array|string $arguments): int
    {
        // handle command empty
        $baseArgs = $arguments[1] ?? '--help';
        $commands = [];

        $this->bootstrap();

        foreach ($this->commands() as $cmd) {
            $commands = array_merge($commands, $cmd->patterns(), $cmd->cmd());

            if ($cmd->isMatch($baseArgs)) {
                $class = $cmd->class();
                $this->app->set($class, fn () => new $class($arguments, $cmd->defaultOption()));

                $call = $this->app->call($cmd->call());

                return $this->exitCode = (is_int($call) ? $call : 0);
            }
        }

        // did you mean
        $count   = 0;
        $similar =  new Style();
        $similar->tap(error(sprintf('Command "%s" is ambiguous. Did you mean one of these?', $baseArgs)));
        /** @noinspection PhpRedundantOptionalArgumentInspection */
        foreach ($this->getSimilarity($baseArgs, $commands, 0.8) as $term => $score) {
            $similar->push('    > ')->push($term)->textDim()->newLines();
            $count++;
        }

        // if command not register
        if (0 === $count) {
            new Style()
                ->tap(error(sprintf('Command "%s" is not defined.', $baseArgs)))
                ->tap(info('Run help command.'))
                ->push('> php omega --help')->textDim()
                ->newLines()
                ->out(false);

            return $this->exitCode = 1;
        }

        $similar->out();

        return $this->exitCode = 1;
    }

    /**
     * Run all configured bootstrappers.
     *
     * This prepares the application before executing a command
     * (e.g., registering config, facades, and service providers).
     *
     * @return void
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function bootstrap(): void
    {
        $this->app->bootstrapWith($this->bootstrappers);
    }

    /**
     * Execute a console command by its signature string.
     *
     * Unlike {@see handle}, this method accepts a signature directly,
     * without requiring the "php" prefix. Parameters may be passed
     * programmatically.
     *
     * @param string $signature Command signature (e.g., "make:model User")
     * @param array<string, string|bool|int|null> $parameter Named parameters
     * @return int Exit code
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws ContainerExceptionInterface Thrown on general container errors, e.g., service not retrievable.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function call(string $signature, array $parameter = []): int
    {
        $arguments = explode(' ', $signature);
        $baseArgs  = $arguments[1] ?? '--help';

        $this->bootstrap();

        foreach ($this->commands() as $cmd) {
            if ($cmd->isMatch($baseArgs)) {
                $class = $cmd->class();
                $this->app->set($class, fn () => new $class($arguments, $parameter));

                $call = $this->app->call($cmd->call());

                return is_int($call) ? $call : 0;
            }
        }

        return 1;
    }

    /**
     * Find the closest matches to a given command string using
     * Jaro-Winkler similarity.
     *
     * @param string $find The input string to compare
     * @param string[] $commands The list of registered command names
     * @param float $threshold Minimum similarity required (default 0.8)
     * @return array<string, float> Commands sorted by similarity
     * @noinspection PhpSameParameterValueInspection
     */
    private function getSimilarity(string $find, array $commands, float $threshold = 0.8): array
    {
        $closest   = [];
        $findLower = strtolower($find);

        foreach ($commands as $command) {
            $commandLower = strtolower($command);
            $similarity   = $this->jaroWinkler($findLower, $commandLower);

            if ($similarity >= $threshold) {
                $closest[$command] = $similarity;
            }
        }

        arsort($closest);

        return $closest;
    }

    /**
     * Compute the Jaro-Winkler similarity between two strings.
     *
     * Produces a score between 0 (no similarity) and 1 (identical).
     * Enhances Jaro similarity by giving extra weight to common prefixes.
     *
     * @param string $find
     * @param string $command
     * @return float
     */
    private function jaroWinkler(string $find, string $command): float
    {
        $jaro = $this->jaro($find, $command);

        // Calculate the prefix length (maximum of 4 characters)
        $prefixLength    = 0;
        $maxPrefixLength = min(strlen($find), strlen($command), 4);
        for ($i = 0; $i < $maxPrefixLength; $i++) {
            if ($find[$i] !== $command[$i]) {
                break;
            }
            $prefixLength++;
        }

        return $jaro + ($prefixLength * 0.1 * (1 - $jaro));
    }

    /**
     * Compute the Jaro similarity between two strings.
     *
     * Produces a score between 0 (no similarity) and 1 (identical).
     * Accounts for character transpositions within a match window.
     *
     * @param string $find
     * @param string $command
     * @return float
     */
    private function jaro(string $find, string $command): float
    {
        $len1 = strlen($find);
        $len2 = strlen($command);

        if ($len1 === 0) {
            return $len2 === 0 ? 1.0 : 0.0;
        }

        $matchDistance = (int) floor(max($len1, $len2) / 2) - 1;

        $str1Matches = array_fill(0, $len1, false);
        $str2Matches = array_fill(0, $len2, false);

        $matches        = 0;
        $transpositions = 0;

        // Find matching characters
        for ($i = 0; $i < $len1; $i++) {
            $start = max(0, $i - $matchDistance);
            $end   = min($i + $matchDistance + 1, $len2);

            for ($j = $start; $j < $end; $j++) {
                if ($str2Matches[$j] || $find[$i] !== $command[$j]) {
                    continue;
                }
                $str1Matches[$i] = true;
                $str2Matches[$j] = true;
                $matches++;
                break;
            }
        }

        if ($matches === 0) {
            return 0.0;
        }

        // Count transpositions
        $k = 0;
        for ($i = 0; $i < $len1; $i++) {
            if (false === $str1Matches[$i]) {
                continue;
            }
            while (false === $str2Matches[$k]) {
                $k++;
            }
            if ($find[$i] !== $command[$k]) {
                $transpositions++;
            }
            $k++;
        }

        $transpositions /= 2;

        return (($matches / $len1) + ($matches / $len2) + (($matches - $transpositions) / $matches)) / 3.0;
    }

    /**
     * Get the last exit status code.
     *
     * @return int Exit status code
     */
    public function exitCode(): int
    {
        return $this->exitCode;
    }

    /**
     * Load and return all registered console commands.
     *
     * @return CommandMap[] The list of command route definitions
     */
    protected function commands(): array
    {
        return Util::loadCommandFromConfig($this->app);
    }
}
