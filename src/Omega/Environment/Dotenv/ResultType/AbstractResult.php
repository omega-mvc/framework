<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\ResultType;

use Omega\Environment\Dotenv\Option\AbstractOption;

abstract class AbstractResult implements ResultInterface
{
    protected $value;

    protected function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function success(): AbstractOption;

    /**
     * {@inheritdoc}
     */
    abstract public function map(callable $f): ResultInterface;

    /**
     * {@inheritdoc}
     */
    abstract public function flatMap(callable $f): ResultInterface;

    /**
     * {@inheritdoc}
     */
    abstract public function error(): AbstractOption;

    /**
     * {@inheritdoc}
     */
    abstract public function mapError(callable $f): ResultInterface;
}
