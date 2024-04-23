<?php

namespace DndSheet;

use DndSheet\Command\CustomYaml;
use DndSheet\Command\DndBeyondApi;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;

class DndSheetApplication extends Application
{
    /**
     * @return Command[]
     */
    protected function getDefaultCommands(): array
    {
        return [new CustomYaml(), new DndBeyondApi(), new HelpCommand(), new ListCommand()];
    }
}
