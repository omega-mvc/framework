<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\ResultType;

use Omega\Environment\Dotenv\Option\AbstractOption;
use Omega\Environment\Dotenv\Option\None;
use Omega\Environment\Dotenv\Option\Some;

class Success extends AbstractResult
{
    private function __construct($value)
    {
        parent::__construct($value);
    }

    /**
     * Create success.
     *
     * @param $value
     * @return Success
     */
    public static function create($value): Success
    {
        return new self($value);
    }

    /**
     * {@inheritdoc}
     */
    public function success(): AbstractOption|Some
    {
        return Some::create($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $f): ResultInterface
    {
        return self::create($f($this->value));
    }

    /**
     * {@inheritdoc}
     */
    public function flatMap(callable $f): ResultInterface
    {
        return $f($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function error(): AbstractOption
    {
        return None::create();
    }

    /**
     * {@inheritdoc}
     */
    public function mapError(callable $f): ResultInterface
    {
        return self::create($this->value);
    }
}
