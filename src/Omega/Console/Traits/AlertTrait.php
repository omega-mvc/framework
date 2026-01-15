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

namespace Omega\Console\Traits;

use Omega\Console\Style\Alert;
use Omega\Console\Style\Decorate;
use Omega\Console\Style\Style;

/**
 * Trait AlertTrait
 *
 * Provides reusable alert rendering methods for terminal output.
 * Supports info, warning, error, and success messages with configurable left margin.
 *
 * @category   Omega
 * @package    Console
 * @subpackges Traits
 * @link       https://omega-mvc.github.io
 * @author     Adriano Giovannini <agisoftt@gmail.com>
 * @copyright  Copyright (c) 2025 - 2026 Adriano Giovannini (https://omega-mvc.github.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0-standalone.html     GPL V3.0+
 * @version    2.0.0
 */
trait AlertTrait
{
    /** @var int Left margin for alert rendering (number of spaces). */
    protected int $marginLeft = 0;

    /**
     * Set the left margin for alert messages.
     *
     * @param int $marginLeft Number of spaces to indent the alert
     * @return AlertTrait|Alert Returns the instance for method chaining
     */
    public function marginLeft(int $marginLeft): self
    {
        $this->marginLeft = $marginLeft;

        return $this;
    }

    /**
     * Render an informational alert message.
     *
     * @param string $info The message to display
     * @return Style Returns a Style instance representing the formatted message
     */
    public function info(string $info): Style
    {
        return new Style()
            ->newLines()
            ->repeat(' ', $this->marginLeft)
            ->push(' INFO ')
            ->bold()
            ->rawReset([Decorate::RESET_BOLD_DIM])
            ->bgBlue()
            ->push(' ')
            ->push($info)
            ->newLines(2);
    }

    /**
     * Render a warning alert message.
     *
     * @param string $warn The warning message to display
     * @return Style Returns a Style instance representing the formatted message
     */
    public function warn(string $warn): Style
    {
        return new Style()
            ->newLines()
            ->repeat(' ', $this->marginLeft)
            ->push(' WARNING ')
            ->bold()
            ->rawReset([Decorate::RESET_BOLD_DIM])
            ->bgYellow()
            ->push(' ')
            ->push($warn)
            ->newLines(2);
    }

    /**
     * Render a failure alert message.
     *
     * @param string $error The failure message to display
     * @return Style Returns a Style instance representing the formatted message
     */
    public function error(string $error): Style
    {
        return new Style()
            ->newLines()
            ->repeat(' ', $this->marginLeft)
            ->push(' ERROR ')
            ->bold()
            ->rawReset([Decorate::RESET_BOLD_DIM])
            ->bgRed()
            ->push(' ')
            ->push($error)
            ->newLines();
    }

    /**
     * Render a success or "ok" alert message.
     *
     * @param string $success The success message to display
     * @return Style Returns a Style instance representing the formatted message
     */
    public function success(string $success): Style
    {
        return new Style()
            ->newLines()
            ->repeat(' ', $this->marginLeft)
            ->push(' SUCCESS ')
            ->bold()
            ->rawReset([Decorate::RESET_BOLD_DIM])
            ->bgGreen()
            ->push(' ')
            ->push($success)
            ->newLines(2);
    }
}
