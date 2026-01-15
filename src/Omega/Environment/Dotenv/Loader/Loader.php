<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Loader;

use Omega\Environment\Dotenv\Parser\Entry;
use Omega\Environment\Dotenv\Parser\Value;
use Omega\Environment\Dotenv\Repository\RepositoryInterface;

use function array_merge;
use function array_reduce;

class Loader implements LoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(RepositoryInterface $repository, array $entries): array
    {
        /** @var array<string, string|null> */
        return array_reduce($entries, static function (array $vars, Entry $entry) use ($repository) {
            $name = $entry->getName();

            $value = $entry->getValue()->map(static function (Value $value) use ($repository) {
                return Resolver::resolve($repository, $value);
            });

            if ($value->isDefined()) {
                $inner = $value->get();
                if ($repository->set($name, $inner)) {
                    return array_merge($vars, [$name => $inner]);
                }
            } else {
                if ($repository->clear($name)) {
                    return array_merge($vars, [$name => null]);
                }
            }

            return $vars;
        }, []);
    }
}
