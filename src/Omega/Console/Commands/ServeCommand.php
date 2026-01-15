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

namespace Omega\Console\Commands;

use Omega\Console\AbstractCommand;
use Omega\Console\Style\Alert;
use Omega\Console\Style\Style;
use Omega\Console\Traits\PrintHelpTrait;

use function Omega\Console\error;
use function shell_exec;

/**
 * ServeCommand
 *
 * Console command that starts the built-in PHP development server
 * for the current Omega application.
 *
 * It allows running the application on a configurable port and,
 * optionally, exposing the server to the local network. This command
 * is intended for development and testing purposes only.
 *
 * @category   Omega
 * @package    Console
 * @subpackage Commands
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 *
 * @property string  $port
 * @property bool $expose
 */
class ServeCommand extends AbstractCommand
{
    use PrintHelpTrait;

    /**
     * Command registration configuration.
     *
     * Defines the pattern used to invoke the command and the method to execute.
     *
     * @var array<int, array<string, mixed>>
     */
    public static array $command = [
        [
            'pattern' => 'serve',
            'fn'      => [ServeCommand::class, 'main'],
            'default' => [
                'port'   => 8000,
                'expose' => false,
            ],
        ],
    ];

    /**
     * Returns a description of the command, its options, and their relations.
     *
     * This is used to generate help output for users.
     *
     * @return array<string, array<string, string|string[]>>
     */
    public function printHelp(): array
    {
        return [
            'commands'  => [
                'serve' => 'Serve server with port number (default 8000)',
            ],
            'options'   => [
                '--port'   => 'Serve with custom port',
                '--expose' => 'Make server run public network',
            ],
            'relation'  => [
                'serve' => ['--port', '--expose'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return int Exit code: always 0.
     */
    public function main(): int
    {
        $port = $this->port;
        if (!is_numeric($port)) {
            error("Port must be numeric");
            return 0;
        }

        $this->launchServer((int)$port, $this->expose);
        return 1;
    }

    /**
     * Launch the PHP built-in development server.
     *
     * This method prints connection information to the console and
     * starts the PHP internal web server, binding either to localhost
     * or to all network interfaces when exposure is enabled.
     *
     * @param int  $port   Port number on which the server will listen
     * @param bool $expose Whether to expose the server to the local network
     * @return void
     */
    private function launchServer(int $port, bool $expose): void
    {
        $localIP = gethostbyname(gethostname());

        $print = new Style('Server running at:');
        $print
            ->newLines()
            ->push('Local')->tabs()->push("http://localhost:$port")->textBlue();

        if ($expose) {
            $print->newLines()->push('Network')->tabs()->push("http://$localIP:$port")->textBlue();
        }

        $print
            ->newLines(2)
            ->push('ctrl+c to stop server')
            ->newLines()
            ->tap(Alert::render()->info('server running...'))
            ->out(false);

        $address = $expose ? '0.0.0.0' : '127.0.0.1';
        shell_exec("php -S " . $address . ":" . $port . " -t public/");
    }
}
