<?php

namespace Tests\loyen\DndbCharacterSheet;

use loyen\DndbCharacterSheet\Exception\CharacterInvalidImportException;
use loyen\DndbCharacterSheet\Importer;
use loyen\DndbCharacterSheet\Model\Character;
use loyen\DndbCharacterSheet\Model\CharacterAbility;
use loyen\DndbCharacterSheet\Model\CharacterClass;
use loyen\DndbCharacterSheet\Model\CharacterHealth;
use loyen\DndbCharacterSheet\Model\CharacterMovement;
use loyen\DndbCharacterSheet\Model\Item;
use PHPUnit\Framework\TestCase;

/**
 * @covers loyen\DndbCharacterSheet\Importer
 * @covers loyen\DndbCharacterSheet\Model\Character
 */
final class ImporterTest extends TestCase
{
    public function dataCharacters(): array
    {
        return [
            [
                __DIR__ . '/Fixtures/character_61111699.json',
                'Will Ager',
                1,
                11,
                [
                    'STR' => 13,
                    'DEX' => 13,
                    'CON' => 13,
                    'INT' => 13,
                    'WIS' => 13,
                    'CHA' => 13
                ],
                [
                    'pp' => 0,
                    'gp' => 25,
                    'ep' => 0,
                    'sp' => 30,
                    'cp' => 100
                ]
            ],
            [
                __DIR__ . '/Fixtures/character_78966354.json',
                'Shuwan Tellalot',
                3,
                22,
                [
                    'STR' => 12,
                    'DEX' => 14,
                    'CON' => 12,
                    'INT' => 13,
                    'WIS' => 8,
                    'CHA' => 15
                ],
                [
                    'pp' => 1,
                    'gp' => 100,
                    'ep' => 0,
                    'sp' => 20,
                    'cp' => 5
                ]
            ]
        ];
    }

    /**
     * @dataProvider dataCharacters
     */
    public function testImportFromFile(
        string $filePath,
        string $characterName,
        int $characterLevel,
        int $characterHealth,
        array $characterAbilityScores,
        array $characterWallet
    ) {
        $character = Importer::importFromFile($filePath);

        $this->assertInstanceOf(Character::class, $character);
        $this->assertEquals($characterName, $character->getName());
        $this->assertEquals($characterLevel, $character->getLevel());
        $this->assertInstanceOf(CharacterHealth::class, $character->getHealth());
        $this->assertEquals($characterHealth, $character->getHealth()->getMaxHitPoints());
        $actualCharacterAbilityScores = $character->getAbilityScores();
        $this->assertContainsOnlyInstancesOf(CharacterAbility::class, $actualCharacterAbilityScores);
        $this->assertEquals(
            $characterAbilityScores['STR'],
            $actualCharacterAbilityScores['STR']->getCalculatedValue()
        );
        $this->assertEquals(
            $characterAbilityScores['DEX'],
            $actualCharacterAbilityScores['DEX']->getCalculatedValue()
        );
        $this->assertEquals(
            $characterAbilityScores['CON'],
            $actualCharacterAbilityScores['CON']->getCalculatedValue()
        );
        $this->assertEquals(
            $characterAbilityScores['INT'],
            $actualCharacterAbilityScores['INT']->getCalculatedValue()
        );
        $this->assertEquals(
            $characterAbilityScores['WIS'],
            $actualCharacterAbilityScores['WIS']->getCalculatedValue()
        );
        $this->assertEquals(
            $characterAbilityScores['CHA'],
            $actualCharacterAbilityScores['CHA']->getCalculatedValue()
        );
        $this->assertContainsOnlyInstancesOf(CharacterClass::class, $character->getClasses());
        $this->assertContainsOnlyInstancesOf(CharacterMovement::class, $character->getMovementSpeeds());
        $this->assertContainsOnlyInstancesOf(Item::class, $character->getInventory());
        $this->assertEquals($characterWallet, $character->getCurrencies());
        $this->assertContainsOnly('array', $character->getProficiencies());
    }

    public function testInvalidCharacterImport()
    {
        $this->expectException(CharacterInvalidImportException::class);
        Importer::importFromJson('[]');
    }
}
