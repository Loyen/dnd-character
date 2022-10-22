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
                15,
                [
                    'STR' => 13,
                    'DEX' => 13,
                    'CON' => 13,
                    'INT' => 13,
                    'WIS' => 13,
                    'CHA' => 13
                ],
                [
                    'cp' => 100,
                    'sp' => 30,
                    'gp' => 25,
                    'ep' => 0,
                    'pp' => 0,
                ]
            ],
            [
                __DIR__ . '/Fixtures/character_78966354.json',
                'Shuwan Tellalot',
                3,
                22,
                13,
                [
                    'STR' => 12,
                    'DEX' => 14,
                    'CON' => 12,
                    'INT' => 13,
                    'WIS' => 8,
                    'CHA' => 15
                ],
                [
                    'cp' => 5,
                    'sp' => 20,
                    'gp' => 100,
                    'ep' => 0,
                    'pp' => 1,
                ]
            ],
            [
                __DIR__ . '/Fixtures/character_82291589.json',
                'Luke "Wu" Eetes',
                1,
                9,
                14,
                [
                    'STR' => 10,
                    'DEX' => 16,
                    'CON' => 12,
                    'INT' => 8,
                    'WIS' => 13,
                    'CHA' => 15
                ],
                [
                    'cp' => 0,
                    'sp' => 0,
                    'gp' => 0,
                    'ep' => 0,
                    'pp' => 0,
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
        int $characterArmorClass,
        array $characterAbilityScores,
        array $characterWallet
    ) {
        $character = Importer::importFromFile($filePath);

        $this->assertInstanceOf(Character::class, $character);
        $this->assertSame($characterName, $character->getName());
        $this->assertSame($characterLevel, $character->getLevel(), 'Character Level');
        $this->assertCharacterHealth($characterHealth, $character->getHealth());
        $this->assertCharacterArmorClass($characterArmorClass, $character->getArmorClass());
        $this->assertCharacterAbilityScores($characterAbilityScores, $character->getAbilityScores());
        $this->assertContainsOnlyInstancesOf(CharacterClass::class, $character->getClasses());
        $this->assertContainsOnlyInstancesOf(CharacterMovement::class, $character->getMovementSpeeds());
        $this->assertContainsOnlyInstancesOf(Item::class, $character->getInventory());
        $this->assertSame($characterWallet, $character->getCurrencies(), 'Wallet');
        $this->assertCharacterProficiencies($character->getProficiencies());
    }

    public function testInvalidCharacterImportThrowsException()
    {
        $this->expectException(CharacterInvalidImportException::class);
        Importer::importFromJson('[]');
    }

    private function assertCharacterAbilityScores($expectedScores, $actualScores)
    {
        $this->assertContainsOnlyInstancesOf(CharacterAbility::class, $actualScores);
        $this->assertSame(
            $expectedScores['STR'],
            $actualScores['STR']->getCalculatedValue(),
            'STR ability score'
        );
        $this->assertSame(
            $expectedScores['DEX'],
            $actualScores['DEX']->getCalculatedValue(),
            'DEX ability score'
        );
        $this->assertSame(
            $expectedScores['CON'],
            $actualScores['CON']->getCalculatedValue(),
            'CON ability score'
        );
        $this->assertSame(
            $expectedScores['INT'],
            $actualScores['INT']->getCalculatedValue(),
            'INT ability score'
        );
        $this->assertSame(
            $expectedScores['WIS'],
            $actualScores['WIS']->getCalculatedValue(),
            'WIS ability score'
        );
        $this->assertSame(
            $expectedScores['CHA'],
            $actualScores['CHA']->getCalculatedValue(),
            'CHA ability score'
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

    private function assertCharacterProficiencies(array $actualProficiencies)
    {
        $this->assertContainsOnly('array', $actualProficiencies, 'Proficiencies');
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
