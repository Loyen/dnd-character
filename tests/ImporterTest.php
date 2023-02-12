<?php

namespace Tests\loyen\DndbCharacterSheet;

use loyen\DndbCharacterSheet\Exception\CharacterInvalidImportException;
use loyen\DndbCharacterSheet\Importer;
use loyen\DndbCharacterSheet\Model\Character;
use loyen\DndbCharacterSheet\Model\CharacterAbility;
use loyen\DndbCharacterSheet\Model\CharacterArmorClass;
use loyen\DndbCharacterSheet\Model\CharacterClass;
use loyen\DndbCharacterSheet\Model\CharacterHealth;
use loyen\DndbCharacterSheet\Model\CharacterMovement;
use loyen\DndbCharacterSheet\Model\CharacterProficiency;
use loyen\DndbCharacterSheet\Model\Item;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Importer::class)]
#[CoversClass(Character::class)]
final class ImporterTest extends TestCase
{
    public static function dataCharacters(): array
    {
        $characterList = [];

        $characterFileDir = __DIR__ . '/Fixtures/';

        foreach (glob($characterFileDir . 'character_*_expected.json') as $filePath) {
            $characterData = \json_decode(
                \file_get_contents($filePath),
                true
            );

            $characterData['apiFilePath'] = $characterFileDir
                . 'character_'
                . $characterData['id']
                . '_api_response.json';

            $characterName = $characterData['id'] . ' - ' . $characterData['name'];

            $characterList[$characterName] = [
                $characterData
            ];
        }

        return $characterList;
    }

    #[DataProvider('dataCharacters')]
    public function testImportFromFile(array $expectedCharacterData)
    {
        $character = Importer::importFromFile($expectedCharacterData['apiFilePath']);

        $this->assertInstanceOf(Character::class, $character);
        $this->assertSame($expectedCharacterData['name'], $character->getName());
        $this->assertSame($expectedCharacterData['level'], $character->getLevel(), 'Character Level');
        $this->assertCharacterAbilityScores($expectedCharacterData['abilityScores'], $character->getAbilityScores());
        $this->assertCharacterHealth($expectedCharacterData['health'], $character->getHealth());
        $this->assertCharacterArmorClass($expectedCharacterData['armorClass'], $character->getArmorClass());
        $this->assertContainsOnlyInstancesOf(CharacterClass::class, $character->getClasses());
        $this->assertCharacterMovementSpeeds($expectedCharacterData['movementSpeeds'], $character->getMovementSpeeds());
        $this->assertContainsOnlyInstancesOf(Item::class, $character->getInventory());
        $this->assertSame($expectedCharacterData['wallet'], $character->getCurrencies(), 'Wallet');
        $this->assertCharacterProficiencies($character->getProficiencies());
    }

    public function testInvalidCharacterImportThrowsException()
    {
        $this->expectException(CharacterInvalidImportException::class);
        Importer::importFromJson('[]');
    }

    private function assertCharacterAbilityScores(array $expectedScores, array $actualScores)
    {
        $this->assertContainsOnlyInstancesOf(CharacterAbility::class, $actualScores);
        $this->assertSame(
            [
                'STR' => $expectedScores['STR']['score'],
                'DEX' => $expectedScores['DEX']['score'],
                'CON' => $expectedScores['CON']['score'],
                'INT' => $expectedScores['INT']['score'],
                'WIS' => $expectedScores['WIS']['score'],
                'CHA' => $expectedScores['CHA']['score']
            ],
            [
                'STR' => $actualScores['STR']->getCalculatedValue(),
                'DEX' => $actualScores['DEX']->getCalculatedValue(),
                'CON' => $actualScores['CON']->getCalculatedValue(),
                'INT' => $actualScores['INT']->getCalculatedValue(),
                'WIS' => $actualScores['WIS']->getCalculatedValue(),
                'CHA' => $actualScores['CHA']->getCalculatedValue()
            ],
            'Ability scores'
        );

        $this->assertSame(
            [
                'STR' => $expectedScores['STR']['modifier'],
                'DEX' => $expectedScores['DEX']['modifier'],
                'CON' => $expectedScores['CON']['modifier'],
                'INT' => $expectedScores['INT']['modifier'],
                'WIS' => $expectedScores['WIS']['modifier'],
                'CHA' => $expectedScores['CHA']['modifier']
            ],
            [
                'STR' => $actualScores['STR']->getCalculatedModifier(),
                'DEX' => $actualScores['DEX']->getCalculatedModifier(),
                'CON' => $actualScores['CON']->getCalculatedModifier(),
                'INT' => $actualScores['INT']->getCalculatedModifier(),
                'WIS' => $actualScores['WIS']->getCalculatedModifier(),
                'CHA' => $actualScores['CHA']->getCalculatedModifier()
            ],
            'Ability modifiers'
        );

        $this->assertSame(
            [
                'STR' => $expectedScores['STR']['savingThrowProficient'],
                'DEX' => $expectedScores['DEX']['savingThrowProficient'],
                'CON' => $expectedScores['CON']['savingThrowProficient'],
                'INT' => $expectedScores['INT']['savingThrowProficient'],
                'WIS' => $expectedScores['WIS']['savingThrowProficient'],
                'CHA' => $expectedScores['CHA']['savingThrowProficient']
            ],
            [
                'STR' => $actualScores['STR']->isSavingThrowProficient(),
                'DEX' => $actualScores['DEX']->isSavingThrowProficient(),
                'CON' => $actualScores['CON']->isSavingThrowProficient(),
                'INT' => $actualScores['INT']->isSavingThrowProficient(),
                'WIS' => $actualScores['WIS']->isSavingThrowProficient(),
                'CHA' => $actualScores['CHA']->isSavingThrowProficient()
            ],
            'Ability saving throw proficiencies'
        );
    }

    private function assertCharacterArmorClass(int $expectedArmorClass, ?CharacterArmorClass $actualArmorClass)
    {
        $this->assertInstanceOf(CharacterArmorClass::class, $actualArmorClass);
        $this->assertSame($expectedArmorClass, $actualArmorClass->getCalculatedValue(), 'Armor Class');
    }

    private function assertCharacterHealth(int $expectedHealth, ?CharacterHealth $actualHealth)
    {
        $this->assertInstanceOf(CharacterHealth::class, $actualHealth);
        $this->assertSame($expectedHealth, $actualHealth->getMaxHitPoints(), 'Maximum HP');
    }

    private function assertCharacterMovementSpeeds(array $expectedMovementSpeeds, array $actualMovementSpeeds)
    {
        $this->assertContainsOnlyInstancesOf(CharacterMovement::class, $actualMovementSpeeds);
        $this->assertSame(
            \json_encode($expectedMovementSpeeds),
            \json_encode($actualMovementSpeeds),
            'Movement speeds'
        );
    }

    private function assertCharacterProficiencies(array $actualProficiencies)
    {
        $this->assertContainsOnly('array', $actualProficiencies, true, 'Proficiencies');
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['abilities'],
            'Abilities proficiencies'
        );
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['armor'],
            'Armor proficiencies'
        );
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['languages'],
            'Languages proficiencies'
        );
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['tools'],
            'Tools proficiencies'
        );
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['weapons'],
            'Weapons proficiencies'
        );
    }
}
