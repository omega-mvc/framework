<?php

declare(strict_types=1);

namespace Omega\Application;

interface ApplicationInterface extends AbstractApplicationInterface
{
    /**
     * Determinate application maintenance mode.
     *
     * @return bool True if the application is currently in maintenance mode.
     */
    public function isDownMaintenanceMode(): bool;

    /**
     * Get down maintenance file config.
     *
     * @return array<string, string|int|null> Maintenance mode configuration data.
     */
    public function getDownData(): array;

    /**
     * Abort application to http exception.
     *
     * @param int                   $code    HTTP status code.
     * @param string                $message Exception message.
     * @param array<string, string> $headers HTTP response headers.
     * @return void
     */
    public function abort(int $code, string $message = '', array $headers = []): void;
}
