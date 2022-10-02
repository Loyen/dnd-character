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
                    'pp' => 1,
                    'gp' => 100,
                    'ep' => 0,
                    'sp' => 20,
                    'cp' => 5
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
                    'pp' => 0,
                    'gp' => 0,
                    'ep' => 0,
                    'sp' => 0,
                    'cp' => 0
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
        $this->assertEquals($characterName, $character->getName());
        $this->assertEquals($characterLevel, $character->getLevel(), 'Character Level');
        $this->assertInstanceOf(CharacterHealth::class, $character->getHealth());
        $this->assertEquals($characterHealth, $character->getHealth()->getMaxHitPoints(), 'Maximum HP');
        $this->assertInstanceOf(CharacterArmorClass::class, $character->getArmorClass());
        $this->assertEquals($characterArmorClass, $character->getArmorClass()->getCalculatedValue(), 'Armor Class');
        $actualCharacterAbilityScores = $character->getAbilityScores();
        $this->assertContainsOnlyInstancesOf(CharacterAbility::class, $actualCharacterAbilityScores);
        $this->assertEquals(
            $characterAbilityScores['STR'],
            $actualCharacterAbilityScores['STR']->getCalculatedValue(),
            'STR ability score'
        );
        $this->assertEquals(
            $characterAbilityScores['DEX'],
            $actualCharacterAbilityScores['DEX']->getCalculatedValue(),
            'DEX ability score'
        );
        $this->assertEquals(
            $characterAbilityScores['CON'],
            $actualCharacterAbilityScores['CON']->getCalculatedValue(),
            'CON ability score'
        );
        $this->assertEquals(
            $characterAbilityScores['INT'],
            $actualCharacterAbilityScores['INT']->getCalculatedValue(),
            'INT ability score'
        );
        $this->assertEquals(
            $characterAbilityScores['WIS'],
            $actualCharacterAbilityScores['WIS']->getCalculatedValue(),
            'WIS ability score'
        );
        $this->assertEquals(
            $characterAbilityScores['CHA'],
            $actualCharacterAbilityScores['CHA']->getCalculatedValue(),
            'CHA ability score'
        );
        $this->assertContainsOnlyInstancesOf(CharacterClass::class, $character->getClasses());
        $this->assertContainsOnlyInstancesOf(CharacterMovement::class, $character->getMovementSpeeds());
        $this->assertContainsOnlyInstancesOf(Item::class, $character->getInventory());
        $this->assertEquals($characterWallet, $character->getCurrencies(), 'Wallet');
        $this->assertContainsOnly('array', $character->getProficiencies(), 'Proficiencies');
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $character->getProficiencies()['abilities'],
            'Abilities proficiencies'
        );
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $character->getProficiencies()['armor'],
            'Armor proficiencies'
        );
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $character->getProficiencies()['languages'],
            'Languages proficiencies'
        );
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $character->getProficiencies()['tools'],
            'Tools proficiencies'
        );
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $character->getProficiencies()['weapons'],
            'Weapons proficiencies'
        );
    }

    public function testInvalidCharacterImport()
    {
        $this->expectException(CharacterInvalidImportException::class);
        Importer::importFromJson('[]');
    }
}
