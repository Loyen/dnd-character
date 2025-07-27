<?php

namespace DndCharacter\Command;

use DndCharacter\Exception\CharacterInvalidImportException;
use DndCharacter\Importer\CustomYaml\CustomYamlImporter;
use DndCharacter\Sheet;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'custom-yaml',
    description: 'Create a character sheet from a YAML schema',
)]
class CustomYaml extends Command
{
    public function __invoke(
        OutputInterface $output,
        #[Argument('File to read.', 'file')]
        string $filePath,
        #[Option('Output in JSON.', 'json')]
        bool $asJson = false,
    ): int {
        if (empty($filePath)) {
            $output->writeln('No file inputted.');

            return Command::FAILURE;
        }

        if (!file_exists($filePath)) {
            $output->writeln('Failed to find file.');

            return Command::FAILURE;
        }

        if (($fileContent = file_get_contents($filePath)) === false) {
            $output->writeln('Failed to read file.');

            return Command::FAILURE;
        }

        try {
            $character = CustomYamlImporter::import($fileContent);
        } catch (CharacterInvalidImportException $e) {
            $output->writeln('Failed to parse file. Error: ' . $e->getMessage());

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
}
