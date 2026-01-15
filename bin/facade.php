<?php

/** @noinspection PhpUnnecessaryCurlyVarSyntaxInspection */

declare(strict_types=1);

use Omega\Console\AbstractCommand;
use Omega\Console\Style\Style;
use Omega\Template\Generate;
use Omega\Template\Method;

use function Omega\Console\error;
use function Omega\Console\info;
use function Omega\Console\success;
use function Omega\Console\warn;

require_once __DIR__ . '/../vendor/autoload.php';

$command = new class ($argv) extends AbstractCommand
{
    private string $facadeFileLocation = '/src/Omega/Support/Facades/';
    private string $facadeNamespace    = 'Omega\\Support\\Facades';

    private function canWrite(): bool
    {
        return $this->getVerbosity() > self::VERBOSITY_QUIET;
    }

    public function entry(): int
    {
        if ('facade:generate' === $this->cmd) {
            return $this->generate();
        }

        if ('facade:update' === $this->cmd) {
            if (
                false !== ($file = $this->option('from-file', false))
                && file_exists(dirname(__DIR__) . $file)
            ) {
                $facades = require_once dirname(__DIR__) . $file;
                $fail    = 0;
                foreach ($facades as $facade => $options) {
                    $options = is_string($options) ? ['accessor' => $options] : $options;
                    if ($this->updater($facade, $options) > 0) {
                        $fail++;
                    }
                }

                $count = count($facades);
                if ($fail > 0) {
                    error("{$fail} of {$count} facades " . ($this->hasOption('dry-run')
                            ? 'failed validation'
                            : 'could not be updated'))->outIf($this->canWrite());

                    return AbstractCommand::FAILURE;
                }

                success('Successfully ' . ($this->hasOption('dry-run')
                        ? 'validated' : 'updated') . " {$count} " . ($count === 1 ? 'facade'
                        : 'facades'))->outIf($this->canWrite());

                return AbstractCommand::SUCCESS;
            }

            return $this->update();
        }

        warn('The command argument is required: facade:generate --accessor')->outIf($this->canWrite());

        return AbstractCommand::INVALID;
    }

    public function generate(): int
    {
        if (false === ($className = $this->option('facade', false))) {
            error('The command argument is required: facade:generate --facade')->outIf($this->canWrite());

            return AbstractCommand::INVALID;
        }

        $accessor  = $this->option('accessor', $className);
        $className = ucfirst($className);

        // get class methods
        $methods = [];
        if (class_exists($accessor)) {
            $reflection = new ReflectionClass($accessor);
            $methods    = $this->getMethodReflection($reflection);
        }

        $facadeClass = $this->generator($className, $accessor, $methods);
        $filename    = dirname(__DIR__) . "{$this->facadeFileLocation}{$className}.php";

        info("Generating new facade {$className}")->outIf($this->canWrite());

        return false === file_put_contents($filename, $facadeClass) ? 1 : 0;
    }

    public function update(): int
    {
        if (
            false === ($facade  = $this->option('facade', false))
            || false === ($accessor = $this->option('accessor', false))
        ) {
            error('The command argument is required: facade:update --facade --accessor')->outIf($this->canWrite());

            return AbstractCommand::INVALID;
        }

        return $this->updater($facade, ['accessor' => $accessor]);
    }

    /**
     * @param array{
     *     accessor?: string,
     *     excludes?: array<string, bool>,
     *     replaces?: array<string, string>,
     *     with?: array<string, array{param?: string[], return?: string}>
     * } $options
     */
    public function updater(string $facade, array $options = []): int
    {
        $accessor        = $options['accessor'];
        $facadeNamespace = "{$this->facadeNamespace}\\{$facade}";
        $methods         = [];

        if (false === class_exists($facadeNamespace)) {
            error("Facade class `{$facade}` is not exists, try generate new facade.")->outIf($this->canWrite(), false);

            return AbstractCommand::INVALID;
        }

        if (false === class_exists($accessor)) {
            error("Facade accessor `{$accessor}` is not found.")->outIf($this->canWrite(), false);

            return AbstractCommand::FAILURE;
        }

        $reflection       = new ReflectionClass($accessor);
        $methods          = $this->getMethodReflection($reflection, $options);
        $reflectionFacade = new ReflectionClass($facadeNamespace);

        $filename = dirname(__DIR__) . "{$this->facadeFileLocation}{$facade}.php";
        $file     = file_get_contents($filename);

        $oldDocblock = $reflectionFacade->getDocComment();
        $newDocblock = ltrim($this->generatorDocBlock($accessor, $methods));

        info("Updating facade `{$facade}`...")
            ->outIf(false === $this->hasOption('dry-run') && $this->canWrite(), false);

        $this
            ->diff($newDocblock, $oldDocblock)
            ->outIf($this->isVeryVerbose());

        if ($this->hasOption('dry-run')) {
            if ($oldDocblock === $newDocblock) {
                success("Facade `{$facade}` is up to date")->outIf($this->canWrite(), false);

                return AbstractCommand::SUCCESS;
            }

            error("Facade `{$facade}` needs to be updated")->outIf($this->canWrite(), false);

            return AbstractCommand::FAILURE;
        }

        return false === file_put_contents(
            $filename,
            str_replace(search: $oldDocblock, replace: $newDocblock, subject: $file)
        ) ? 1 : 0;
    }

    /**
     * Generate a facade class.
     *
     * @param string[] $methods list of method signatures for the docblock
     */
    private function generator(string $class_name, string $accessor, array $methods = []): string
    {
        $generator = new Generate($class_name);
        $generator->tabIndent(' ');
        $generator->tabSize(4);
        $generator->setEndWithNewLine();

        $generator->setDeclareStrictTypes();
        $generator->namespace($this->facadeNamespace);
        $generator->setFinal();
        $generator->extend('AbstractFacade');
        foreach ($methods as $docMethod) {
            $generator->addComment("@method static {$docMethod}");
        }
        $generator->addLineComment();
        $generator->addComment("@see {$accessor}");

        $accessor_alias = $this->getAccessorAlias($accessor);
        $generator->addMethod('getFacadeAccessor')
            ->visibility(Method::PROTECTED_)
            ->setStatic()
            ->body(["return {$accessor_alias};"]);

        return $generator->__toString();
    }

    /**
     * @param string[] $methods
     */
    private function generatorDocBlock(string $accessor, array $methods): string
    {
        $generator = new Generate('');
        foreach ($methods as $docMethod) {
            $generator->addComment("@method static {$docMethod}");
        }
        $generator->addLineComment();
        $generator->addComment("@see {$accessor}");

        return $generator->generateComment(tabIndent: ' ');
    }

    /**
     * Get all public method signatures of a class.
     *
     * @param ReflectionClass<object> $class
     * @param array{
     *     accessor?: string,
     *     excludes?: array<string, bool>,
     *     replaces?: array<string, string>,
     *     with?: array<string, array{param?: string[], return?: string}>
     * } $options
     * @return string[] list of method signatures
     */
    private function getMethodReflection(ReflectionClass $class, array $options = []): array
    {
        $buffer              = [];
        $methods             = [];
        $maxReturnTypeLength = 0;
        $fullClassName       = '\\' . $class->getName();
        $ignoreMethod        = [
            'offsetExists' => true,
            'offsetGet'    => true,
            'offsetSet'    => true,
            'offsetUnset'  => true,
        ];
        // options
        $optExcludes = $options['excludes'] ?? [];
        $optReplaces = $options['replaces'] ?? [];
        $optWith     = $options['with'] ?? [];

        foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (
                $method->isConstructor()
                || $method->isDestructor()
                || $method->isDeprecated()
                || str_starts_with($method->getName(), '__')
                || array_key_exists($method->getName(), $ignoreMethod)
                || array_key_exists($method->getName(), $optExcludes) // exclude from options
            ) {
                continue;
            }

            // pre-require
            $returnType = null;
            $params     = [];

            // doc block lexer
            if (false !== ($docComment = $method->getDocComment())) {
                $lines  = extractDocLines($docComment);
                $blocks = groupMultilineBlocks($lines);

                foreach ($blocks as $line) {
                    if (null !== ($param = parseParamLine($line))) {
                        $type                   = replaceUnionTypes($param['type'], $optReplaces);
                        $params[$param['name']] = "{$type} {$param['name']}";

                        continue;
                    }

                    if (null !== ($returnResult = parseReturnLine($line))) {
                        $returnType = replaceUnionTypes($returnResult, $optReplaces);
                    }
                }
            }

            // Get return type as string
            $returnType ??= $method->hasReturnType()
                ? $this->getTypeFromReflection($method->getReturnType())
                : 'mixed';

            if ('self' === $returnType) {
                $returnType = $fullClassName;
            }

            if (strlen($returnType) > $maxReturnTypeLength) {
                $maxReturnTypeLength = strlen($returnType);
            }

            // Build parameter string
            foreach ($method->getParameters() as $param) {
                $paramName = '$' . $param->getName();
                if (isset($params[$paramName])) {
                    $defaultValue       = $this->getParameterDefaultValueString($param);
                    $params[$paramName] = $params[$paramName] . $defaultValue;

                    if (isset($optWith[$method->getName()]['param'][$param->getName()])) {
                        $params[$paramName] = $optWith[$method->getName()]['param'][$param->getName()] . $defaultValue;
                    }

                    continue;
                }

                $params[$paramName] = $this->getParameterString($param);
            }

            // always offering parameter and return
            if (isset($optWith[$method->getName()]['return'])) {
                $returnType = $optWith[$method->getName()]['return'];
            }

            $buffer[] = [
                'returnType' => $returnType,
                'name'       => $method->getName(),
                'params'     => implode(', ', $params),
            ];
        }

        foreach ($buffer as $item) {
            $pad       = str_pad($item['returnType'], $maxReturnTypeLength);
            $methods[] = "{$pad} {$item['name']}({$item['params']})";
        }

        return $methods;
    }

    private function getTypeFromReflection(ReflectionType $type): string
    {
        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();

            // Add prefix for PHP built-in classes and System namespace classes
            if (str_starts_with($typeName, 'Omega\\') || class_exists($typeName, false)) {
                $typeName = '\\' . $typeName;
            }

            if ($type->allowsNull() && $typeName !== 'mixed' && $typeName !== 'null') {
                $typeName = '?' . $typeName;
            }

            return $typeName;
        }

        if ($type instanceof ReflectionUnionType) {
            $types = [];
            foreach ($type->getTypes() as $t) {
                $types[] = $this->getTypeFromReflection($t);
            }

            return implode('|', $types);
        }

        return 'mixed';
    }

    private function getParameterString(ReflectionParameter $param): string
    {
        $param_string = '';

        // type
        if ($param->hasType()) {
            $param_string .= $this->getTypeFromReflection($param->getType()) . ' ';
        }

        // reference
        if ($param->isPassedByReference()) {
            $param_string .= '&';
        }

        // variadic
        if ($param->isVariadic()) {
            $param_string .= '...';
        }

        // name
        $param_string .= '$' . $param->getName();

        // default value
        $param_string .= $this->getParameterDefaultValueString($param);

        return $param_string;
    }

    private function getParameterDefaultValueString(ReflectionParameter $param): string
    {
        $param_string = '';

        // default value
        if ($param->isOptional() && $param->isDefaultValueAvailable()) {
            $default_value = $param->getDefaultValue();
            $default_value = match (true) {
                is_array($default_value)  => '[]',
                is_bool($default_value)   => $default_value ? 'true' : 'false',
                is_string($default_value) => "'" . $default_value . "'",
                is_null($default_value)   => 'null',
                default                   => $default_value,
            };
            $param_string .= ' = ' . $default_value;
        }

        return $param_string;
    }

    private function getAccessorAlias(string $accessor): string
    {
        if ($this->hasOption('alias')) {
            return "'" . $this->option('alias') . "'";
        }

        if (class_exists($accessor)) {
            return "\\{$accessor}::class";
        }

        return "'{$accessor}'";
    }

    private function diff(string $new, string $old): Style
    {
        $oldLines = explode("\n", $old);
        $newLines = explode("\n", $new);

        $lcs     = $this->computeLCS($oldLines, $newLines);
        $diffOps = $this->generateDiffOperations($oldLines, $newLines, $lcs);

        // Build styled output
        $out = new Style();
        foreach ($diffOps as $op) {
            switch ($op['type']) {
                case 'unchanged':
                    $out->push('  ' . $op['line'])->textDim()->newLines();
                    break;
                case 'removed':
                    $out->push('- ' . $op['line'])->textRed()->newLines();
                    break;
                case 'added':
                    $out->push('+ ' . $op['line'])->textGreen()->newLines();
                    break;
            }
        }

        return $out;
    }

    /**
     * Compute LCS table.
     *
     * @param string[] $oldLines
     * @param string[] $newLines
     *
     * @return array<int, int[]>
     */
    private function computeLCS(array $oldLines, array $newLines): array
    {
        $oldCount = count($oldLines);
        $newCount = count($newLines);

        // Initialize LCS table
        $lcs = array_fill(0, $oldCount + 1, array_fill(0, $newCount + 1, 0));

        // Fill LCS table using dynamic programming
        for ($i = 1; $i <= $oldCount; $i++) {
            for ($j = 1; $j <= $newCount; $j++) {
                if ($oldLines[$i - 1] === $newLines[$j - 1]) {
                    $lcs[$i][$j] = $lcs[$i - 1][$j - 1] + 1;
                } else {
                    $lcs[$i][$j] = max($lcs[$i - 1][$j], $lcs[$i][$j - 1]);
                }
            }
        }

        return $lcs;
    }

    /**
     * Generate diff based on LCS.
     *
     * @param string[]          $oldLines
     * @param string[]          $newLines
     * @param array<int, int[]> $lcs
     *
     * @return array<int, array{type: 'added'|'removed'|'unchanged', line: string}>
     */
    private function generateDiffOperations(array $oldLines, array $newLines, array $lcs): array
    {
        $operations = [];
        $i          = count($oldLines);
        $j          = count($newLines);

        // Backtrack through LCS table to generate diff operations
        while ($i > 0 || $j > 0) {
            if ($i > 0 && $j > 0 && $oldLines[$i - 1] === $newLines[$j - 1]) {
                array_unshift($operations, [
                    'type' => 'unchanged',
                    'line' => $oldLines[$i - 1],
                ]);
                $i--;
                $j--;
            } elseif ($j > 0 && ($i === 0 || $lcs[$i][$j - 1] >= $lcs[$i - 1][$j])) {
                array_unshift($operations, [
                    'type' => 'added',
                    'line' => $newLines[$j - 1],
                ]);
                $j--;
            } elseif ($i > 0 && ($j === 0 || $lcs[$i][$j - 1] < $lcs[$i - 1][$j])) {
                array_unshift($operations, [
                    'type' => 'removed',
                    'line' => $oldLines[$i - 1],
                ]);
                $i--;
            }
        }

        return $operations;
    }
};

exit($command->entry());

// helper

/**
 * @return string[]
 */
function extractDocLines(string $docString): array
{
    $docString = trim($docString);
    $docString = preg_replace('/^\/\*\*/', '', $docString);
    $docString = preg_replace('/\*\/$/', '', $docString);

    $lines = preg_split('/\r\n|\r|\n/', $docString);

    $cleanLines = [];
    foreach ($lines as $line) {
        $line = trim($line);
        $line = preg_replace('/^\*\s?/', '', $line);

        if (false === empty($line)) {
            $cleanLines[] = $line;
        }
    }

    return $cleanLines;
}

/**
 * @param string[] $lines
 *
 * @return string[]
 */
function groupMultilineBlocks(array $lines): array
{
    $grouped      = [];
    $currentBlock = '';
    $isInBlock    = false;

    foreach ($lines as $line) {
        if (preg_match('/^@\w+/', $line)) {
            if (true === $isInBlock && false === empty($currentBlock)) {
                $grouped[] = trim($currentBlock);
            }

            $currentBlock = $line;
            $isInBlock    = true;
        } elseif (true === $isInBlock) {
            $currentBlock .= ' ' . $line;
        } else {
            $grouped[] = $line;
        }
    }

    if (true === $isInBlock && false === empty($currentBlock)) {
        $grouped[] = trim($currentBlock);
    }

    return $grouped;
}

/**
 * @return array{type: string, name: string}|null
 */
function parseParamLine(string $line): ?array
{
    if (!str_starts_with($line, '@param')) {
        return null;
    }

    $content          = ltrim(substr($line, 6)); // 6 = strlen("@param")
    $remainingContent = '';
    $type             = '';
    $bracketDepth     = 0; // < and >
    $braceDepth       = 0; // { and }
    $i                = 0;

    while ($i < strlen($content)) {
        $char = $content[$i];

        if ($char === '<') {
            $bracketDepth++;
            $type .= $char;
        } elseif ($char === '>') {
            $bracketDepth--;
            $type .= $char;
        } elseif ($char === '{') {
            $braceDepth++;
            $type .= $char;
        } elseif ($char === '}') {
            $braceDepth--;
            $type .= $char;
        } elseif ($char === ' ' && $bracketDepth === 0 && $braceDepth === 0) {
            $remainingContent = ltrim(substr($content, $i));
            break;
        } else {
            $type .= $char;
        }
        $i++;
    }

    if (str_starts_with($remainingContent, '$')) {
        $spacePos     = strpos($remainingContent, ' ');
        $variableName = false !== $spacePos
            ? substr($remainingContent, 0, $spacePos)
            : $remainingContent;

        return [
            'type' => trim($type),
            'name' => $variableName,
        ];
    }

    return null;
}

function parseReturnLine(string $line): ?string
{
    if (!str_starts_with($line, '@return')) {
        return null;
    }

    $content = ltrim(substr($line, 7)); // 7 = strlen("@return")

    $type         = '';
    $bracketDepth = 0;
    $braceDepth   = 0;
    $i            = 0;

    while ($i < strlen($content)) {
        $char = $content[$i];

        if ($char === '<') {
            $bracketDepth++;
            $type .= $char;
        } elseif ($char === '>') {
            $bracketDepth--;
            $type .= $char;
        } elseif ($char === '{') {
            $braceDepth++;
            $type .= $char;
        } elseif ($char === '}') {
            $braceDepth--;
            $type .= $char;
        } elseif ($char === ' ' && $bracketDepth === 0 && $braceDepth === 0) {
            break;
        } else {
            $type .= $char;
        }
        $i++;
    }

    return trim($type);
}

/**
 * Replace types in a union type string.
 * This not part of parse doc, its helper for
 * replace types.
 *
 * @param array<string, string> $replaces
 */
function replaceUnionTypes(string $typeString, array $replaces): string
{
    if (isset($replaces[$typeString])) {
        return $replaces[$typeString];
    }

    $result       = '';
    $currentToken = '';
    $bracketDepth = 0;
    $braceDepth   = 0;

    for ($i = 0; $i < strlen($typeString); $i++) {
        $char = $typeString[$i];

        if (in_array($char, ['<', '>', '{', '}', ',', ' ', ':', '|', '[', ']'])) {
            if (false === empty($currentToken) && isset($replaces[$currentToken])) {
                $result .= $replaces[$currentToken];
            } else {
                $result .= $currentToken;
            }
            $currentToken = '';

            match ($char) {
                '<'     => $bracketDepth++,
                '>'     => $bracketDepth--,
                '{'     => $braceDepth++,
                '}'     => $braceDepth--,
                default => null,
            };

            $result .= $char;
        } else {
            $currentToken .= $char;
        }
    }

    // Last token
    if (false === empty($currentToken) && isset($replaces[$currentToken])) {
        $result .= $replaces[$currentToken];
    } else {
        $result .= $currentToken;
    }

    return $result;
}
