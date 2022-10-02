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
        $this->assertInstanceOf(CharacterHealth::class, $character->getHealth());
        $this->assertSame($characterHealth, $character->getHealth()->getMaxHitPoints(), 'Maximum HP');
        $this->assertInstanceOf(CharacterArmorClass::class, $character->getArmorClass());
        $this->assertSame($characterArmorClass, $character->getArmorClass()->getCalculatedValue(), 'Armor Class');
        $actualCharacterAbilityScores = $character->getAbilityScores();
        $this->assertContainsOnlyInstancesOf(CharacterAbility::class, $actualCharacterAbilityScores);
        $this->assertSame(
            $characterAbilityScores['STR'],
            $actualCharacterAbilityScores['STR']->getCalculatedValue(),
            'STR ability score'
        );
        $this->assertSame(
            $characterAbilityScores['DEX'],
            $actualCharacterAbilityScores['DEX']->getCalculatedValue(),
            'DEX ability score'
        );
        $this->assertSame(
            $characterAbilityScores['CON'],
            $actualCharacterAbilityScores['CON']->getCalculatedValue(),
            'CON ability score'
        );
        $this->assertSame(
            $characterAbilityScores['INT'],
            $actualCharacterAbilityScores['INT']->getCalculatedValue(),
            'INT ability score'
        );
        $this->assertSame(
            $characterAbilityScores['WIS'],
            $actualCharacterAbilityScores['WIS']->getCalculatedValue(),
            'WIS ability score'
        );
        $this->assertSame(
            $characterAbilityScores['CHA'],
            $actualCharacterAbilityScores['CHA']->getCalculatedValue(),
            'CHA ability score'
        );
        $this->assertContainsOnlyInstancesOf(CharacterClass::class, $character->getClasses());
        $this->assertContainsOnlyInstancesOf(CharacterMovement::class, $character->getMovementSpeeds());
        $this->assertContainsOnlyInstancesOf(Item::class, $character->getInventory());
        $this->assertSame($characterWallet, $character->getCurrencies(), 'Wallet');
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

    public function testInvalidCharacterImportThrowsException()
    {
        $this->expectException(CharacterInvalidImportException::class);
        Importer::importFromJson('[]');
    }
}
