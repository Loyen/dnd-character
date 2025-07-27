<?php

namespace DndCharacter\Command;

use DndCharacter\Exception\CharacterInvalidImportException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use DndCharacter\Importer\DndBeyond\DndBeyondImporter;
use DndCharacter\Importer\DndBeyond\Exception\CharacterFileReadException;
use DndCharacter\Sheet;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'dndbeyond',
    description: 'Create a character sheet from DNDBeyond API character data',
)]
class DndBeyondApi extends Command
{
    public function __invoke(
        OutputInterface $output,
        #[Option('File to read.', 'file', 'f')]
        ?string $filePath = null,
        #[Option('Character ID to read from API.', 'characterid', 'c')]
        ?string $characterId = null,
        #[Option('Output in JSON.', 'json')]
        bool $asJson = false,
    ): int {
        if (!empty($filePath)) {
            return $this->fromFile($filePath, $asJson, $output);
        } elseif (!empty($characterId)) {
            return $this->fromApi($characterId, $asJson, $output);
        }

        return Command::SUCCESS;
    }

    public static function fromApi(string $characterId, bool $asJson, OutputInterface $output): int
    {
        $characterId = filter_var($characterId, \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE);

        if (!$characterId) {
            $output->writeln('No character ID inputted.');

            return Command::FAILURE;
        }

        try {
            $client = new Client([
                'base_uri' => 'https://character-service.dndbeyond.com/',
                'timeout' => 20,
            ]);

            $response = $client->request('GET', 'character/v5/character/' . $characterId);

            $character = DndBeyondImporter::import((string) $response->getBody());
        } catch (GuzzleException $e) {
            $output->writeln(
                'Could not get a response from DNDBeyond character API. Error: ' . $e->getMessage(),
            );

            return Command::FAILURE;
        } catch (CharacterInvalidImportException $e) {
            $output->writeln(
                'Failed to parse the response from DNDBeyond character API. Error: ' . $e->getMessage(),
            );

            return Command::FAILURE;
        }

        if ($asJson) {
            $output->writeln((string) json_encode(
                $character,
                \JSON_PRETTY_PRINT,
            ));
        } else {
            $sheet = new Sheet();
            $output->writeln($sheet->render($character));
        }

        return Command::SUCCESS;
    }

    public static function fromFile(string $filePath, bool $asJson, OutputInterface $output): int
    {
        if (empty($filePath) || !file_exists($filePath)) {
            throw new CharacterFileReadException('No file inputted.');
        }

        $fileContent = file_get_contents($filePath)
            ?: throw new CharacterFileReadException('Failed to read inputted file.');
        $character = DndBeyondImporter::import($fileContent);

        if ($asJson) {
            $output->writeln((string) json_encode(
                $character,
                \JSON_PRETTY_PRINT,
            ));
        } else {
            $sheet = new Sheet();
            $output->writeln($sheet->render($character));
        }

        return Command::SUCCESS;
    }
}
