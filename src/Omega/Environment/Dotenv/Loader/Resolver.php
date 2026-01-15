<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Loader;

use Omega\Environment\Dotenv\Option\AbstractOption;
use Omega\Environment\Dotenv\Parser\Value;
use Omega\Environment\Dotenv\Repository\RepositoryInterface;
use Omega\Environment\Dotenv\Util\Regex;
use Omega\Environment\Dotenv\Util\Str;

use function array_reduce;

class Resolver
{
    /**
     * This class is a singleton.
     *
     * @return void
     */
    private function __construct()
    {
    }

    /**
     * Resolve the nested variables in the given value.
     *
     * Replaces ${varName} patterns in the allowed positions in the variable
     * value by an existing environment variable.
     *
     * @param RepositoryInterface $repository
     * @param Value               $value
     * @return string
     */
    public static function resolve(RepositoryInterface $repository, Value $value): string
    {
        return array_reduce($value->getVars(), static function (string $s, int $i) use ($repository) {
            return Str::substr($s, 0, $i) . self::resolveVariable($repository, Str::substr($s, $i));
        }, $value->getChars());
    }

    /**
     * Resolve a single nested variable.
     *
     * @param RepositoryInterface $repository
     * @param string              $str
     * @return string
     */
    private static function resolveVariable(RepositoryInterface $repository, string $str): string
    {
        return Regex::replaceCallback(
            '/\A\${([a-zA-Z0-9_.]+)}/',
            static function (array $matches) use ($repository) {
                /** @var string */
                return AbstractOption::fromValue($repository->get($matches[1]))->getOrElse($matches[0]);
            },
            $str,
            1
        )->success()->getOrElse($str);
    }
}
