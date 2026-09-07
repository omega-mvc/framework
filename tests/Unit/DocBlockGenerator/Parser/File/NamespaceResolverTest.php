<?php

declare(strict_types=1);

namespace Tests\DocBlockGenerator\Parser\File;

use Omega\DocBlockGenerator\Parser\File\NamespaceResolver;

covers(NamespaceResolver::class);

it('parses use statements', function (): void {
    $sources = <<<'PHP'
    <?php

    declare(strict_types=1);

    use Omega\Http\Request;
    use Omega\Http\Response;
    use Omega\Router\Route;
    use Omega\Router\Router;
    use Omega\DocBlockGenerator\VarExport;
    use Omega\DocBlockGenerator\VarExport\Buffer;
    PHP;

    $parser = new NamespaceResolver();
    $uses   = $parser->resolve($sources);

    $expected = [
        'Omega\Http\Request',
        'Omega\Http\Response',
        'Omega\Router\Route',
        'Omega\Router\Router',
        'Omega\DocBlockGenerator\VarExport',
        'Omega\DocBlockGenerator\VarExport\Buffer',
    ];

    expect($uses)->toEqual($expected);
});

it('parses group use statements', function (): void {
    $sources = <<<'PHP'
    <?php

    declare(strict_types=1);

    use Omega\Http\{Request, Response};
    use Omega\Router\{Route, Router};
    use Omega\DocBlockGenerator\VarExport;
    use Omega\DocBlockGenerator\VarExport\Buffer;
    PHP;

    $parser = new NamespaceResolver();
    $uses   = $parser->resolve($sources);

    $expected = [
        'Omega\Http\Request',
        'Omega\Http\Response',
        'Omega\Router\Route',
        'Omega\Router\Router',
        'Omega\DocBlockGenerator\VarExport',
        'Omega\DocBlockGenerator\VarExport\Buffer',
    ];

    expect($uses)->toEqual($expected);
});

it('handles a file with no use statements', function (): void {
    $sources = '<?php class MyClass {}';
    $parser  = new NamespaceResolver();
    $uses    = $parser->resolve($sources);

    expect($uses)->toBeEmpty();
});
