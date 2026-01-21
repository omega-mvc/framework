<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Omega\Container\Exceptions\BindingResolutionException;
use Omega\Container\Exceptions\CircularAliasException;
use Omega\Container\Exceptions\EntryNotFoundException;
use Omega\Http\Response;
use ReflectionException;

class AssetController extends AbstractController
{
    /**
     * @return Response
     * @throws BindingResolutionException Thrown when resolving a binding fails.
     * @throws CircularAliasException Thrown when alias resolution loops recursively.
     * @throws EntryNotFoundException Thrown when no entry exists for the identifier.
     * @throws ReflectionException Thrown when the requested class or interface cannot be reflected.
     */
    public function handle(): Response
    {
        return view('Asset', [
            "title" => "Document Title",
        ]);
    }
}
