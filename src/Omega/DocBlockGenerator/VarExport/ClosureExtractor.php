<?php

declare(strict_types=1);

namespace Omega\DocBlockGenerator\VarExport;

use InvalidArgumentException;
use ReflectionFunction;

use function array_filter;
use function array_keys;
use function array_map;
use function array_reduce;
use function array_slice;
use function count;
use function explode;
use function file;
use function file_exists;
use function implode;
use function is_array;
use function is_readable;
use function is_string;
use function min;
use function rtrim;
use function str_ends_with;
use function str_replace;
use function strlen;
use function strspn;
use function substr;
use function token_get_all;
use function trim;

use const PHP_EOL;
use const PHP_INT_MAX;
use const T_DOUBLE_ARROW;
use const T_FN;
use const T_FUNCTION;
use const T_OPEN_TAG;

/**
 * ClosureExtractor - Extract and normalize closure source code using tokenization.
 *
 * Features:
 * - Token-based parsing
 * - Handles function() and fn() syntax
 * - Tracks brace/parenthesis depth
 * - Normalizes indentation
 * - Returns structured data
 */
final class ClosureExtractor
{
    /**
     * Extract closure from ReflectionFunction.
     *
     * @return array{
     *   lines: string[],
     *   original: string,
     *   normalized: string,
     *   metadata: array{
     *     startLine: int,
     *     endLine: int,
     *     file: string,
     *     isSingleLine: bool,
     *     isArrowFunction: bool,
     *     minIndent: int
     *   },
     *   ast: array{
     *     type: string,
     *     isArrowFunction: bool,
     *     parameters: string[],
     *     body: string,
     *     returnType: string|null
     *   }
     * }
     */
    public function extract(ReflectionFunction $reflection): array
    {
        [$file, $startLine, $endLine] = $this->validateReflection($reflection);

        $isSingleLine = $startLine === $endLine;

        $sourceLines  = $this->readSourceLines($file, $startLine, $endLine);
        $originalCode = implode('', $sourceLines);

        // Extract pure closure code
        $closureCode = $this->extractFromSourceCode($originalCode, $file, $startLine);

        // Tokenize the code with PHP tags
        $tokens = $this->tokenize('<?php ' . $closureCode);
        $ast    = $this->buildClosureAST($tokens);

        // Normalize closure code
        $closureCode     = $this->normalizeClosureCode($closureCode);
        $lines           = explode("\n", $closureCode);
        $minIndent       = $this->findMinimumIndentation($lines);
        $normalizedLines = $this->removeIndentation($lines, $minIndent);
        $normalizedCode  = implode(PHP_EOL, $normalizedLines);

        return [
            'lines'      => $normalizedLines,
            'original'   => $originalCode,
            'normalized' => $normalizedCode,
            'metadata'   => [
                'startLine'       => $startLine,
                'endLine'         => $endLine,
                'file'            => $file,
                'isSingleLine'    => $isSingleLine,
                'isArrowFunction' => $ast['isArrowFunction'],
                'minIndent'       => $minIndent,
            ],
            'ast' => $ast,
        ];
    }

    /**
     * Validate that reflection is compilable.
     *
     * @return array{0: string, 1: int, 2: int}
     */
    private function validateReflection(ReflectionFunction $reflection): array
    {
        $file      = $reflection->getFileName();
        $startLine = $reflection->getStartLine();
        $endLine   = $reflection->getEndLine();

        if (false === $file) {
            throw new InvalidArgumentException(
                'Cannot extract runtime-created closure. Closure must be defined in a source file.'
            );
        }

        if (false === $startLine || false === $endLine) {
            throw new InvalidArgumentException(
                'Cannot extract closure with unknown start or end line.'
            );
        }

        if (false === file_exists($file)) {
            throw new InvalidArgumentException("Source file not found: {$file}");
        }

        if (false === is_readable($file)) {
            throw new InvalidArgumentException("Source file not readable: {$file}");
        }

        return [$file, $startLine, $endLine];
    }

    /**
     * Read source lines from file.
     *
     * @return string[]
     */
    private function readSourceLines(string $file, int $startLine, int $endLine): array
    {
        $lines = file($file);

        if (false === $lines) {
            throw new InvalidArgumentException("Cannot read file: {$file}");
        }

        return array_slice($lines, $startLine - 1, $endLine - $startLine + 1);
    }

    /**
     * Extract pure closure code from source code.
     * Handles both single-line and multi-line closures.
     * Removes any prefix (like array keys) before the function/fn keyword.
     */
    private function extractFromSourceCode(string $code, string $file, int $lineNumber): string
    {
        $tokens = $this->tokenize('<?php ' . $code);

        $starts = array_keys(array_filter(
            $tokens,
            fn (array|string $token): bool => $this->isToken($token, T_FUNCTION) || $this->isToken($token, T_FN)
        ));
        $closureStart = $starts[0] ?? null;

        if (null === $closureStart) {
            throw new InvalidArgumentException("Could not extract closure from line {$lineNumber} in {$file}");
        }

        $isArrowFunction = $this->isToken($tokens[$closureStart], T_FN);
        $tail            = array_slice($tokens, $closureStart, null, true);

        if ($isArrowFunction) {
            $arrows = array_keys(array_filter(
                $tail,
                fn (array|string $token): bool => $this->isToken($token, T_DOUBLE_ARROW)
            ));
            $arrow = $arrows[0] ?? null;

            if (null === $arrow) {
                throw new InvalidArgumentException("Could not extract closure from line {$lineNumber} in {$file}");
            }

            $delimiters = array_keys(array_filter(
                array_slice($tokens, $arrow + 1, null, true),
                fn (array|string $token): bool =>
                    $this->isChar($token, ',') || $this->isChar($token, ';') || $this->isChar($token, ')')
            ));
            $delimiter  = $delimiters[0] ?? null;
            $closureEnd = null === $delimiter ? null : $delimiter - 1;
        } else {
            $braces = array_keys(array_filter(
                $tail,
                fn (array|string $token): bool => $this->isChar($token, '{') || $this->isChar($token, '}')
            ));
            /** @var array{depth: int, end: int|null} $scan */
            $scan = array_reduce(
                $braces,
                fn (array $carry, int $key): array => $this->reduceBraceScan($carry, $key, $tokens),
                ['depth' => 0, 'end' => null]
            );
            $closureEnd = $scan['end'];
        }

        if (null === $closureEnd) {
            throw new InvalidArgumentException("Could not extract closure from line {$lineNumber} in {$file}");
        }

        $closureTokens = array_slice($tokens, $closureStart, $closureEnd - $closureStart + 1);

        return $this->tokensToString($closureTokens);
    }

    /**
     * @param array{depth: int, end: int|null} $carry
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     * @return array{depth: int, end: int|null}
     */
    private function reduceBraceScan(array $carry, int $key, array $tokens): array
    {
        if (null !== $carry['end']) {
            return $carry;
        }

        if ($this->isChar($tokens[$key], '{')) {
            $carry['depth']++;
            return $carry;
        }

        $carry['depth']--;

        if (0 === $carry['depth']) {
            $carry['end'] = $key;
        }

        return $carry;
    }

    /**
     * Tokenize PHP code.
     *
     * @return array<int, array{0: int, 1: string, 2: int}|string>
     */
    private function tokenize(string $code): array
    {
        return token_get_all($code);
    }

    /**
     * Build AST-like structure from tokens.
     *
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     *
     * @return array{
     *   type: string,
     *   isArrowFunction: bool,
     *   parameters: string[],
     *   body: string,
     *   returnType: string|null
     * }
     */
    private function buildClosureAST(array $tokens): array
    {
        /**
         * @var array{
         *   ast: array{
         *     type: string,
         *     isArrowFunction: bool,
         *     parameters: string[],
         *     body: string,
         *     returnType: string|null
         *   },
         *   state: string,
         *   buffer: string
         * } $carry
         */
        $carry = [
            'ast' => [
                'type'            => 'closure',
                'isArrowFunction' => false,
                'parameters'      => [],
                'body'            => '',
                'returnType'      => null,
            ],
            'state'  => 'initial',
            'buffer' => '',
        ];

        $carry                = array_reduce($tokens, $this->reduceClosureAst(...), $carry);
        $carry['ast']['body'] = trim($carry['buffer']);

        return $carry['ast'];
    }

    /**
     * @param array{
     *   ast: array{
     *     type: string,
     *     isArrowFunction: bool,
     *     parameters: string[],
     *     body: string,
     *     returnType: string|null
     *   },
     *   state: string,
     *   buffer: string
     * } $carry
     * @param array{0: int, 1: string, 2: int}|string $token
     *
     * @return array{
     *   ast: array{
     *     type: string,
     *     isArrowFunction: bool,
     *     parameters: string[],
     *     body: string,
     *     returnType: string|null
     *   },
     *   state: string,
     *   buffer: string
     * }
     */
    private function reduceClosureAst(array $carry, array|string $token): array
    {
        $ast    = $carry['ast'];
        $state  = $carry['state'];
        $buffer = $carry['buffer'];

        if ($this->isToken($token, T_OPEN_TAG)) {
            // The PHP open tag is not part of the closure source.
        } elseif ('initial' === $state && $this->isToken($token, T_FUNCTION)) {
            $ast['isArrowFunction'] = false;
            $state                  = 'parameters';
        } elseif ('initial' === $state && $this->isToken($token, T_FN)) {
            $ast['isArrowFunction'] = true;
            $state                  = 'parameters';
        } elseif ('parameters' === $state && $this->isChar($token, '(')) {
            $state = 'inside_params';
        } elseif ('inside_params' === $state && $this->isChar($token, ')')) {
            $ast['parameters'][] = trim($buffer);
            $buffer              = '';
            $state               = 'after_params';
        } elseif ('inside_params' === $state) {
            $buffer .= $this->tokenToString($token);
        } elseif ('after_params' === $state && $this->isToken($token, T_USE)) {
            $state = 'use_clause';
        } elseif ('after_params' === $state && $this->isChar($token, ':')) {
            $state = 'return_type';
        } elseif ('after_params' === $state && ($this->isToken($token, T_DOUBLE_ARROW) || $this->isChar($token, '{'))) {
            $state  = 'body';
            $buffer = $this->tokenToString($token);
        } elseif ('return_type' === $state && ($this->isChar($token, '{') || $this->isToken($token, T_DOUBLE_ARROW))) {
            $ast['returnType'] = trim($buffer);
            $buffer            = $this->tokenToString($token);
            $state             = 'body';
        } elseif ('return_type' === $state) {
            $buffer .= $this->tokenToString($token);
        } elseif ('body' === $state) {
            $buffer .= $this->tokenToString($token);
        }

        return ['ast' => $ast, 'state' => $state, 'buffer' => $buffer];
    }

    /**
     * Convert tokens back to string.
     *
     * @param array<int, array{0: int, 1: string, 2: int}|string> $tokens
     */
    private function tokensToString(array $tokens): string
    {
        $code = '';

        foreach ($tokens as $token) {
            $code .= $this->tokenToString($token);
        }

        return $code;
    }

    /**
     * Convert single token to string.
     *
     * @param array{0: int, 1: string, 2: int}|string $token
     */
    private function tokenToString(array|string $token): string
    {
        return is_array($token) ? $token[1] : $token;
    }

    /**
     * Check if token is specific type.
     *
     * @param array<int, int|string>|string $token
     */
    private function isToken(array|string $token, int $type): bool
    {
        return is_array($token) && $token[0] === $type;
    }

    /**
     * Check if token is specific character.
     *
     * @param array<int, int|string>|string $token
     */
    private function isChar(array|string $token, string $char): bool
    {
        return is_string($token) && $token === $char;
    }

    private function normalizeClosureCode(string $closureCode): string
    {
        // VarExport will handle final output formatting with the appropriate line endings.
        $closureCode = str_replace(["\r\n", "\r"], "\n", $closureCode);

        $lines = explode("\n", $closureCode);

        $nonBlank = array_keys(array_filter(
            $lines,
            static fn (string $line): bool => '' !== trim($line)
        ));

        if ([] === $nonBlank) {
            return '';
        }

        $last    = $nonBlank[count($nonBlank) - 1];
        $trimmed = rtrim($lines[$last]);

        if (str_ends_with($trimmed, ',')) {
            $lines[$last] = rtrim(substr($trimmed, 0, -1));
        }

        return implode("\n", array_slice($lines, 0, $last + 1));
    }

    /**
     * Find minimum indentation in lines.
     *
     * @param string[] $lines
     */
    private function findMinimumIndentation(array $lines): int
    {
        if (count($lines) > 1) {
            return strspn($lines[count($lines) - 1], " \t\n\r\f\v");
        }

        $indents = array_map(
            static fn (string $line): int => strspn($line, " \t\n\r\f\v"),
            array_filter($lines, static fn (string $line): bool => '' !== trim($line))
        );

        $minIndent = [] === $indents ? PHP_INT_MAX : min($indents);

        return PHP_INT_MAX === $minIndent ? 0 : $minIndent;
    }

    /**
     * Remove indentation from lines.
     *
     * For multi-line closures, preserve the first line (opening brace) as-is,
     * and remove indentation from the rest based on the closing brace's indentation.
     *
     * @param string[] $lines
     *
     * @return string[]
     */
    private function removeIndentation(array $lines, int $indent): array
    {
        if (0 === $indent) {
            return $lines;
        }

        $count = count($lines);

        // For multi-line closures, preserve the first line (opening brace) as-is.
        return array_map(
            static function (int|string $i, string $line) use ($indent, $count): string {
                if (0 === $i && $count > 1) {
                    return $line;
                }

                if ('' === trim($line)) {
                    return '';
                }

                return substr($line, min($indent, strlen($line)));
            },
            array_keys($lines),
            $lines
        );
    }

    /**
     * Validate single line doesn't have multiple closures.
     */
    public function validateSingleLine(string $line, int $lineNumber): void
    {
        $closureCount = count(array_filter(
            $this->tokenize('<?php ' . $line),
            fn (array|string $token): bool => $this->isToken($token, T_FUNCTION) || $this->isToken($token, T_FN)
        ));

        if ($closureCount > 1) {
            throw new InvalidArgumentException(
                "Multiple closures detected on line {$lineNumber}. Each closure must be on separate line(s).\n" .
                'Line: ' . trim($line)
            );
        }
    }
}
