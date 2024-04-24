<?php

namespace DndCharacter;

use DndCharacter\Command\CustomYaml;
use DndCharacter\Command\DndBeyondApi;
use Symfony\Component\Console\Application as BaseApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\HelpCommand;
use Symfony\Component\Console\Command\ListCommand;

class Application extends BaseApplication
{
    /**
     * @return Command[]
     */
    protected function getDefaultCommands(): array
    {
        return [new CustomYaml(), new DndBeyondApi(), new HelpCommand(), new ListCommand()];
    }
}
