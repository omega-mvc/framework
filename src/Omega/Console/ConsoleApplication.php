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

use Exception;
use Omega\Application\Application;
use Omega\Config\ConfigRepository;
use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Support\Bootstrap\BootProviders;
use Omega\Support\Bootstrap\ConfigProviders;
use Omega\Support\Bootstrap\RegisterFacades;
use Omega\Support\Bootstrap\RegisterProviders;
use Psr\Container\ContainerExceptionInterface;
use ReflectionException;
use Symfony\Component\Console\Application as SymfonyConsole;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

use function is_array;

/**
 * Console application entry point for Omega.
 *
 * This class acts as a bridge between the Omega application container
 * and the Symfony Console component. It is responsible for bootstrapping
 * the application, resolving configured commands, and delegating execution
 * to the Symfony console runtime.
 *
 * The console lifecycle is:
 * 1. Bootstrap the application (providers, config, etc.)
 * 2. Resolve command classes from configuration
 * 3. Register commands into Symfony Console
 * 4. Execute the console application
 *
 * This implementation keeps Omega decoupled from the console engine,
 * allowing Symfony Console to handle input parsing, command resolution,
 * and execution flow.
 *
 * @category  Omega
 * @package   Console
 * @link      https://omega-mvc.github.io
 * @author    Adriano Giovannini <agisoftt@gmail.com>
 * @copyright Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license   https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version   2.0.0
 */
class ConsoleApplication
{
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
    public function __construct(protected Application $app)
    {
    }

    /**
     * Handle a console request.
     *
     * This method bootstraps the application (if not already bootstrapped),
     * prepares input and output instances, registers all configured commands,
     * and delegates execution to the Symfony Console application.
     *
     * @param array<int, string>|InputInterface|null $input  Raw CLI arguments or a pre-built input instance.
     * @param OutputInterface|null                   $output Output instance; defaults to ConsoleOutput if null.
     * @return int Exit status code returned by the console application.
     * @throws BindingResolutionException If a container binding cannot be resolved.
     * @throws CircularAliasException If a circular alias is detected in the container.
     * @throws ContainerExceptionInterface For generic container-related errors.
     * @throws EntryNotFoundException If a required container entry is missing.
     * @throws Exception For any generic runtime error.
     * @throws ReflectionException If a class cannot be reflected during resolution.
     */
    public function handle(array|InputInterface|null $input = null, OutputInterface|null $output = null): int
    {
        $this->bootstrap();

        $input  = is_array($input) ? new ArgvInput($input) : ($input ?? new ArgvInput());
        $output = $output ?? new ConsoleOutput();

        $omega = new ConsoleLogo('Omega Framework', $this->app->getVersion('2.0.0'));

        $this->configureCommandLoader($omega);

        return $omega->run($input, $output);
    }

    /**
     * Bootstrap the application if it has not already been bootstrapped.
     *
     * Executes the configured bootstrappers, which typically register
     * configuration, facades, and service providers into the container.
     *
     * @return void
     * @throws BindingResolutionException If a container binding cannot be resolved.
     * @throws CircularAliasException If a circular alias is detected.
     * @throws ContainerExceptionInterface For generic container errors.
     * @throws EntryNotFoundException If a required container entry is missing.
     * @throws ReflectionException If a class cannot be reflected.
     */
    protected function bootstrap(): void
    {
        if (!$this->app->bootstrapped) {
            $this->app->bootstrapWith($this->bootstrappers);
        }
    }

    /**
     * Configure the Symfony Console command loader.
     *
     * Retrieves the command map from the configuration repository and assigns
     * an OmegaCommandLoader to the Symfony Console instance. This enables
     * lazy command resolution through the Omega container.
     *
     * Expected configuration format:
     *
     * [
     *     'route:list' => RouteCommand::class,
     *     'cache:clear' => CacheClearCommand::class,
     * ]
     *
     * @param SymfonyConsole $console The Symfony Console application instance.
     * @return void
     * @throws BindingResolutionException If a container binding cannot be resolved.
     * @throws CircularAliasException If a circular alias is detected.
     * @throws ContainerExceptionInterface For generic container errors.
     * @throws EntryNotFoundException If a required container entry is missing.
     * @throws ReflectionException If a class cannot be reflected.
     */
    protected function configureCommandLoader(SymfonyConsole $console): void
    {
        $config   = $this->app->make(ConfigRepository::class);
        $commands = $config->get('commands', []);

        $console->setCommandLoader(
            new CommandLoader($this->app, $commands)
        );
    }
}
