<?php

declare(strict_types=1);

namespace Omega\Environment\Dotenv\Repository\Adapter;

use Omega\Environment\Dotenv\Option\AbstractOption;

interface AdapterInterface extends ReaderInterface, WriterInterface
{
    /**
     * Create a new instance of the adapter, if it is available.
     *
     * @return AbstractOption<AdapterInterface>
     */
    public static function create();
}
