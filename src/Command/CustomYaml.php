<?php

namespace loyen\DndbCharacterSheet\Command;

use Composer\Script\Event;
use loyen\DndbCharacterSheet\Importer\CustomYaml\CustomYamlImporter;
use loyen\DndbCharacterSheet\Importer\ImporterException;
use loyen\DndbCharacterSheet\Sheet;

class CustomYaml
{
    public static function fromFile(Event $event): void
    {
        $exitCode = 0;

        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        require_once $vendorDir . '/autoload.php';

        $arguments = $event->getArguments();
        $filePath = array_pop($arguments);

        if (!$filePath || !file_exists($filePath)) {
            throw new ImporterException('No file inputted.');
        }

        $fileContent = file_get_contents($filePath)
            ?: throw new ImporterException('Failed to read inputted file.');
        $character = CustomYamlImporter::import($fileContent);

        if (\in_array('--json', $arguments, true)) {
            echo json_encode(
                $character,
                \JSON_PRETTY_PRINT,
            );
        } else {
            $sheet = new Sheet();
            echo $sheet->render($character);
        }

        echo \PHP_EOL;
        exit($exitCode);
    }
}
