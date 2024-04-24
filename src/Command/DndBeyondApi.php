<?php

namespace DndCharacter\Command;

use DndCharacter\Exception\CharacterInvalidImportException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use DndCharacter\Importer\DndBeyond\DndBeyondImporter;
use DndCharacter\Importer\DndBeyond\Exception\CharacterFileReadException;
use DndCharacter\Sheet;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'dndbeyond',
    description: 'Create a character sheet from DNDBeyond API character data',
)]
class DndBeyondApi extends Command
{
    protected function configure(): void
    {
        $this->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'File to read.');
        $this->addOption('characterid', 'c', InputOption::VALUE_REQUIRED, 'Character ID to read from API.');
        $this->addOption('json', null, InputOption::VALUE_NONE, 'Output in JSON.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('file') !== null) {
            return $this->fromFile($input, $output);
        } elseif ($input->getOption('characterid') !== null) {
            return $this->fromApi($input, $output);
        }

        return Command::SUCCESS;
    }

    public static function fromApi(InputInterface $input, OutputInterface $output): int
    {
        $characterId = filter_var($input->getOption('characterid'), \FILTER_VALIDATE_INT, \FILTER_NULL_ON_FAILURE);

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

        if ($input->getOption('json')) {
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

    public static function fromFile(InputInterface $input, OutputInterface $output): int
    {
        $filePath = $input->getOption('file');

        if (!$filePath || !file_exists($filePath)) {
            throw new CharacterFileReadException('No file inputted.');
        }

        $fileContent = file_get_contents($filePath)
            ?: throw new CharacterFileReadException('Failed to read inputted file.');
        $character = DndBeyondImporter::import($fileContent);

        if ($input->getOption('json')) {
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
