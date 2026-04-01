<?php

namespace Omega\Console\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AsCommand
{
    /**
     * @param string $name Il nome del comando (es. 'app:user-clean')
     * @param string|null $description Una breve descrizione
     * @param array $aliases Alias del comando
     * @param bool $hidden Se nascondere il comando dalla lista
     * @param array $arguments Mappa di argomenti [nome => [mode, description, default]]
     * @param array $options Mappa di opzioni [nome => [shortcut, mode, description, default]]
     */
    public function __construct(
        public string $name,
        public ?string $description = null,
        public array $aliases = [],
        public bool $hidden = false,
        public array $arguments = [],
        public array $options = []
    ) {}
}
