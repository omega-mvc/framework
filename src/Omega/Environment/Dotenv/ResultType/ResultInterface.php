<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\ResultType;

use Omega\Environment\Dotenv\Option\AbstractOption;

interface ResultInterface
{
    /**
     * Get the success option value.
     *
     * @return AbstractOption
     */
    public function success(): AbstractOption;

    /**
     * Map over the success value.
     *
     * @param callable $f
     * @return ResultInterface
     */
    public function map(callable $f): ResultInterface;

    /**
     * Flat map over the success value.
     *
     * @param callable $f
     * @return ResultInterface
     */
    public function flatMap(callable $f): ResultInterface;

    /**
     * Get the error option value.
     *
     * @return AbstractOption
     */
    public function error(): AbstractOption;

    /**
     * Map over the error value.
     *
     * @param callable $f
     * @return ResultInterface
     */
    public function mapError(callable $f): ResultInterface;
}
