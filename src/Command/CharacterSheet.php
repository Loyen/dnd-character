<?php

namespace loyen\DndbCharacterSheet\Command;

use Composer\Script\Event;
use loyen\DndbCharacterSheet\Importer;
use loyen\DndbCharacterSheet\Sheet;

class CharacterSheet
{
    public static function fromAPI(Event $event): void
    {
        $exitCode = 0;

        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        require_once $vendorDir . '/autoload.php';

        $arguments = $event->getArguments();
        $characterId = \array_pop($arguments);

        $characterId = $characterId ?? throw new \Exception('No character ID inputted.');
        $characterId = intval($characterId);

        $character = Importer::importFromApiById($characterId);

        if (\in_array('--json', $arguments, true)) {
            echo \json_encode(
                $character,
                \JSON_PRETTY_PRINT
            );
        } else {
            $sheet = new Sheet();
            echo $sheet->render($character);
        }

        echo \PHP_EOL;
        exit($exitCode);
    }

    public static function fromFile(Event $event): void
    {
        $exitCode = 0;

        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        require_once $vendorDir . '/autoload.php';

        $arguments = $event->getArguments();
        $filePath = \array_pop($arguments);

        $characterFilePath = $filePath ?? throw new \Exception('No file inputted.');
        $character = Importer::importFromFile($characterFilePath);

        if (\in_array('--json', $arguments, true)) {
            echo \json_encode(
                $character,
                \JSON_PRETTY_PRINT
            );
        } else {
            $sheet = new Sheet();
            echo $sheet->render($character);
        }

        echo \PHP_EOL;
        exit($exitCode);
    }
}
