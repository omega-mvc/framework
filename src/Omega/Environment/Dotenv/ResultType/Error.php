<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\ResultType;

use Omega\Environment\Dotenv\Option\AbstractOption;
use Omega\Environment\Dotenv\Option\None;
use Omega\Environment\Dotenv\Option\Some;

class Error extends AbstractResult
{
    private function __construct($value)
    {
        parent::__construct($value);
    }

    /**
     * Create success.
     *
     * @param $value
     * @return Error
     */
    public static function create($value): Error
    {
        return new self($value);
    }

    /**
     * {@inheritdoc}
     */
    public function success(): AbstractOption|None
    {
        return None::create();
    }

    /**
     * {@inheritdoc}
     */
    public function map(callable $f): AbstractResult
    {
        return self::create($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function flatMap(callable $f): AbstractResult
    {
        return self::create($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function error(): Some
    {
        return Some::create($this->value);
    }

    /**
     * {@inheritdoc}
     */
    public function mapError(callable $f): AbstractResult
    {
        return self::create($f($this->value));
    }
}
