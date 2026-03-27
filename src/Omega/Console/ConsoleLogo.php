<?php

declare(strict_types=1);

namespace Omega\Console;

use Symfony\Component\Console\Application as SymfonyConsole;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Custom Symfony Console application for Omega.
 *
 * Adds framework-specific behavior such as rendering the ASCII logo
 * before command execution.
 */
class ConsoleLogo extends SymfonyConsole
{
    /**
     * Executes the console application.
     *
     * Overrides the default run cycle to render the Omega logo
     * before delegating execution to Symfony.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     */
    public function doRun(InputInterface $input, OutputInterface $output): int
    {
        $this->renderLogo($output);

        return parent::doRun($input, $output);
    }

    /**
     * Render the Omega ASCII logo.
     *
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function renderLogo(OutputInterface $output): void
    {
        $output->writeln("
<fg=cyan;options=bold>      ____  __  ____________ _____ _ </>
<fg=cyan;options=bold>     / __ \/  |/  / ____/ __ `/ __ \/ /</>
<fg=cyan;options=bold>    / / / / /|_/ / __/ / /_/ / /_/ / / </>
<fg=cyan;options=bold>   / /_/ / /  / / /___/ \__, /\__,_/_/  </>
<fg=cyan;options=bold>   \____/_/  /_/_____/____/____/(_)    </>
");
    }
}
