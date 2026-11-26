<?php

declare(strict_types=1);

namespace Omega\DocBlockGenerator\Parser\File;

use function array_filter;
use function array_keys;
use function array_search;
use function array_slice;
use function array_unique;
use function array_values;
use function array_walk;
use function count;
use function file_get_contents;
use function in_array;
use function is_array;
use function is_file;
use function min;
use function token_get_all;

use const T_AS;
use const T_CONST;
use const T_FUNCTION;
use const T_NAME_QUALIFIED;
use const T_NS_SEPARATOR;
use const T_STRING;
use const T_USE;

final class NamespaceResolver
{
    /**
     * @return string[]
     */
    public function resolveFile(string $file): array
    {
        if (false === is_file($file)) {
            return [];
        }

        $sources = (string) file_get_contents($file);

        return $this->resolve($sources);
    }

    /**
     * @return string[]
     */
    public function resolve(string $sources): array
    {
        $tokens = token_get_all($sources);
        $uses   = [];

        $positions = array_keys(array_filter($tokens, fn (array|string $token): bool => $this->isToken($token, T_USE)));

        array_walk(
            $positions,
            function (int $i) use ($tokens, &$uses): void {
                $cursor = $i + 1;
                $this->parseUseStatement($tokens, $cursor, $uses);
            }
        );

        return array_values(array_unique($uses));
    }

    /**
     * Resolve only class imports (not functions or constants).
     *
     * @return string[]
     */
    public function resolveClasses(string $sources): array
    {
        $tokens = token_get_all($sources);
        $uses   = [];

        $positions = array_keys(array_filter($tokens, fn (array|string $token): bool => $this->isToken($token, T_USE)));

        array_walk(
            $positions,
            function (int $i) use ($tokens, &$uses): void {
                $cursor = $i + 1;
                $this->parseClassUseStatement($tokens, $cursor, $uses);
            }
        );

        return array_values(array_unique($uses));
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     * @param string[]                                             $uses
     */
    private function parseUseStatement(array $tokens, int &$i, array &$uses): void
    {
        $this->parseUse($tokens, $i, $uses, false);
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     * @param string[]                                             $uses
     */
    private function parseClassUseStatement(array $tokens, int &$i, array &$uses): void
    {
        $this->parseUse($tokens, $i, $uses, true);
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     * @param string[]                                             $uses
     */
    private function parseUse(array $tokens, int &$i, array &$uses, bool $classesOnly): void
    {
        $end        = $this->findTerminator($tokens, $i);
        $base       = '';
        $isFunction = false;
        $isConst    = false;
        $skipAlias  = false;

        $section = array_slice($tokens, $i, $end - $i);

        array_walk(
            $section,
            function (array|string $token) use (
                &$base,
                &$isFunction,
                &$isConst,
                &$skipAlias,
                &$uses,
                $classesOnly
            ): void {
                if (true === $skipAlias) {
                    if (true === $this->isToken($token, T_STRING)) {
                        $skipAlias = false;
                    }

                    return;
                }

                if (true === $this->isToken($token, T_AS)) {
                    $skipAlias = true;

                    return;
                }

                if (true === $classesOnly && true === $this->isToken($token, T_FUNCTION)) {
                    $isFunction = true;

                    return;
                }

                if (true === $classesOnly && true === $this->isToken($token, T_CONST)) {
                    $isConst = true;

                    return;
                }

                if (true === $this->isNameToken($token)) {
                    $base .= $token[1];

                    return;
                }

                if (',' === $token) {
                    $this->appendUse($base, $uses, $classesOnly, $isFunction, $isConst);
                    $base       = '';
                    $isFunction = false;
                    $isConst    = false;
                }
            }
        );

        $terminator = $tokens[$end] ?? null;

        match (true) {
            true === $skipAlias => null,
            ';' === $terminator => $this->appendUse($base, $uses, $classesOnly, $isFunction, $isConst),
            '{' === $terminator => match (true) {
                $classesOnly => $this->parseGroupedClassUse($tokens, $end, $base, $uses, $isFunction, $isConst),
                default      => $this->parseGroupedUse($tokens, $end, $base, $uses),
            },
            default => null,
        };

        $i = $end;
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     * @param string[]                                             $uses
     */
    private function parseGroupedUse(array $tokens, int $braceIndex, string $base, array &$uses): void
    {
        $this->parseGrouped($tokens, $braceIndex, $base, $uses, false, false, false);
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     * @param string[]                                             $uses
     */
    private function parseGroupedClassUse(
        array $tokens,
        int $braceIndex,
        string $base,
        array &$uses,
        bool $isFunction,
        bool $isConst
    ): void {
        $this->parseGrouped($tokens, $braceIndex, $base, $uses, true, $isFunction, $isConst);
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     * @param string[]                                             $uses
     */
    private function parseGrouped(
        array $tokens,
        int $braceIndex,
        string $base,
        array &$uses,
        bool $classesOnly,
        bool $isFunction,
        bool $isConst
    ): void {
        $closing    = array_search('}', array_slice($tokens, $braceIndex + 1), true);
        $hasClosing = false !== $closing;
        $end        = true === $hasClosing ? $braceIndex + 1 + $closing : count($tokens);

        $segment      = '';
        $childIsFunc  = $isFunction;
        $childIsConst = $isConst;

        $section = array_slice($tokens, $braceIndex + 1, $end - $braceIndex - 1);

        array_walk(
            $section,
            function (array|string $token) use (
                &$segment,
                &$childIsFunc,
                &$childIsConst,
                &$uses,
                $classesOnly,
                $base,
                $isFunction,
                $isConst
            ): void {
                if (true === $classesOnly && true === $this->isToken($token, T_FUNCTION)) {
                    $childIsFunc = true;

                    return;
                }

                if (true === $classesOnly && true === $this->isToken($token, T_CONST)) {
                    $childIsConst = true;

                    return;
                }

                if (true === $this->isNameToken($token)) {
                    $segment .= $token[1];

                    return;
                }

                if (',' === $token) {
                    if ('' !== $segment) {
                        $this->appendUse($base . $segment, $uses, $classesOnly, $childIsFunc, $childIsConst);
                    }

                    $segment      = '';
                    $childIsFunc  = $isFunction;
                    $childIsConst = $isConst;
                }
            }
        );

        match (true) {
            false === $hasClosing => null,
            '' === $segment       => null,
            default               => $this->appendUse(
                $base . $segment,
                $uses,
                $classesOnly,
                $childIsFunc,
                $childIsConst
            ),
        };
    }

    /**
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     */
    private function findTerminator(array $tokens, int $start): int
    {
        $tail      = array_slice($tokens, $start);
        $semicolon = array_search(';', $tail, true);
        $brace     = array_search('{', $tail, true);

        return min(
            false === $semicolon ? count($tokens) : $semicolon + $start,
            false === $brace ? count($tokens) : $brace + $start
        );
    }

    /**
     * @param array{0: int, 1: string, 2: int}|string $token
     */
    private function isToken(array|string $token, int $id): bool
    {
        if (false === is_array($token)) {
            return false;
        }

        return $id === $token[0];
    }

    /**
     * @param array{0: int, 1: string, 2: int}|string $token
     */
    private function isNameToken(array|string $token): bool
    {
        if (false === is_array($token)) {
            return false;
        }

        return in_array($token[0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED], true);
    }

    /**
     * @param string[] $uses
     */
    private function appendUse(string $base, array &$uses, bool $classesOnly, bool $isFunction, bool $isConst): void
    {
        match (true) {
            false === $classesOnly => $uses[] = $base,
            true === $isFunction   => null,
            true === $isConst      => null,
            '' === $base           => null,
            default                => $uses[] = $base,
        };
    }
}
