<?php

namespace loyen\DndbCharacterLight\Commands;

use Composer\Script\Event;
use loyen\DndbCharacterLight\Character\CharacterImporter;

class CharacterSheet
{
    public static function fromFile(Event $event): void
    {
        $exitCode = 0;

        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        require_once $vendorDir . '/autoload.php';

        $characterFilePath = $event->getArguments()[0] ?? throw new \Exception('No file inputted.');
        $characterFileContent = \file_get_contents($characterFilePath);

        $character = CharacterImporter::importFromJson($characterFileContent);

        echo \json_encode(
            $character,
            \JSON_PRETTY_PRINT
        );

        echo \PHP_EOL;
        exit($exitCode);
    }
}
