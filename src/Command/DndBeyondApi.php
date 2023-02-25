<?php

namespace loyen\DndbCharacterSheet\Command;

use Composer\Script\Event;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use loyen\DndbCharacterSheet\Importer\DndBeyond\DndBeyondImporter;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Exception\CharacterAPIException;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Exception\CharacterFileReadException;
use loyen\DndbCharacterSheet\Sheet;

class DndBeyondApi
{
    public static function fromApi(Event $event): void
    {
        $exitCode = 0;

        $vendorDir = $event->getComposer()->getConfig()->get('vendor-dir');
        require_once $vendorDir . '/autoload.php';

        $arguments = $event->getArguments();
        $characterId = \filter_var(\array_pop($arguments), FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);

        if (!$characterId) {
            throw new \Exception('No character ID inputted.');
        }

        try {
            $client = new Client([
                'base_uri'  => 'https://character-service.dndbeyond.com/',
                'timeout'   => 20
            ]);

            $response = $client->request('GET', 'character/v5/character/' . $characterId);

            $character = DndBeyondImporter::import((string) $response->getBody());
        } catch (GuzzleException $e) {
            throw new CharacterAPIException('Could not get a response from DNDBeyond character API. Message: ' . $e->getMessage());
        }

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
        $filePath = \filter_var(\array_pop($arguments), FILTER_SANITIZE_STRING, FILTER_NULL_ON_FAILURE);

        if (!$filePath || !\file_exists($filePath)) {
            throw new CharacterFileReadException('No file inputted.');
        }

        $fileContent = \file_get_contents($filePath)
            ?: throw new CharacterFileReadException('Failed to read inputted file.');

        $character = DndBeyondImporter::import($fileContent);

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
