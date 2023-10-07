<?php

namespace Tests\loyen\DndbCharacterSheet\Importer\DndBeyond;

use loyen\DndbCharacterSheet\Exception\CharacterInvalidImportException;
use loyen\DndbCharacterSheet\Importer\DndBeyond\DndBeyondImporter;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiBookSource;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiCharacter;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiChoice;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiClass;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiClassDefinition;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiClassDefinitionFeature;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiClassFeature;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiClassFeatureDefinition;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiDice;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiFeat;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiFeatDefinition;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiInventoryItem;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiInventoryItemDefinition;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiLevelScale;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiModifier;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiOption;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiOptionDefinition;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiProperty;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiRace;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiStat;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\List\ApiProficiencyGroupEntityTypeId;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\Source;
use loyen\DndbCharacterSheet\Model\AbilityType;
use loyen\DndbCharacterSheet\Model\Character;
use loyen\DndbCharacterSheet\Model\CharacterAbility;
use loyen\DndbCharacterSheet\Model\CharacterArmorClass;
use loyen\DndbCharacterSheet\Model\CharacterClass;
use loyen\DndbCharacterSheet\Model\CharacterFeature;
use loyen\DndbCharacterSheet\Model\CharacterHealth;
use loyen\DndbCharacterSheet\Model\CharacterMovement;
use loyen\DndbCharacterSheet\Model\CharacterProficiency;
use loyen\DndbCharacterSheet\Model\Item;
use loyen\DndbCharacterSheet\Model\SourceMaterial;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ApiBookSource::class)]
#[CoversClass(ApiCharacter::class)]
#[CoversClass(ApiChoice::class)]
#[CoversClass(ApiClass::class)]
#[CoversClass(ApiClassDefinition::class)]
#[CoversClass(ApiClassDefinitionFeature::class)]
#[CoversClass(ApiClassFeature::class)]
#[CoversClass(ApiClassFeatureDefinition::class)]
#[CoversClass(ApiDice::class)]
#[CoversClass(ApiFeat::class)]
#[CoversClass(ApiFeatDefinition::class)]
#[CoversClass(ApiInventoryItem::class)]
#[CoversClass(ApiInventoryItemDefinition::class)]
#[CoversClass(ApiLevelScale::class)]
#[CoversClass(ApiModifier::class)]
#[CoversClass(ApiOption::class)]
#[CoversClass(ApiOptionDefinition::class)]
#[CoversClass(ApiProficiencyGroupEntityTypeId::class)]
#[CoversClass(ApiProperty::class)]
#[CoversClass(ApiRace::class)]
#[CoversClass(ApiStat::class)]
#[CoversClass(DndBeyondImporter::class)]
#[CoversClass(Source::class)]
#[UsesClass(AbilityType::class)]
#[UsesClass(Character::class)]
#[UsesClass(CharacterAbility::class)]
#[UsesClass(CharacterArmorClass::class)]
#[UsesClass(CharacterClass::class)]
#[UsesClass(CharacterFeature::class)]
#[UsesClass(CharacterHealth::class)]
#[UsesClass(CharacterMovement::class)]
#[UsesClass(CharacterProficiency::class)]
#[UsesClass(Item::class)]
#[UsesClass(SourceMaterial::class)]
final class DndBeyondImporterTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    public static function dataCharacters(): array
    {
        $characterList = [];

        $characterFileDir = __DIR__ . '/Fixtures/';

        foreach (\glob($characterFileDir . 'character_*_expected.json') ?: [] as $filePath) {
            $characterData = \json_decode(
                \file_get_contents($filePath) ?: '',
                true
            );

            $characterData['apiFilePath'] = $characterFileDir
                . 'character_'
                . $characterData['id']
                . '_api_response.json';

            $characterName = $characterData['id'] . ' - ' . $characterData['name'];

            $characterList[$characterName] = [
                $characterData,
            ];
        }

        return $characterList;
    }

    /**
     * @param array<string, mixed> $expectedCharacterData
     */
    #[DataProvider('dataCharacters')]
    public function testImport(array $expectedCharacterData): void
    {
        $character = DndBeyondImporter::import(
            \file_get_contents($expectedCharacterData['apiFilePath']) ?: ''
        );

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
        $this->assertCharacterProficiencies(
            $expectedCharacterData['proficiencies'],
            $character->getProficiencies()
        );
    }

    public function testInvalidCharacterImportThrowsException(): void
    {
        $this->expectException(CharacterInvalidImportException::class);
        DndBeyondImporter::import('[]');
    }

    /**
     * @param array<string, array{score: int, modifier: int, savingThrowProficient: bool}> $expectedScores
     * @param array<string, CharacterAbility>                                              $actualScores
     */
    private function assertCharacterAbilityScores(array $expectedScores, array $actualScores): void
    {
        $this->assertContainsOnlyInstancesOf(CharacterAbility::class, $actualScores);
        $this->assertSame(
            [
                'STR' => $expectedScores['STR']['score'],
                'DEX' => $expectedScores['DEX']['score'],
                'CON' => $expectedScores['CON']['score'],
                'INT' => $expectedScores['INT']['score'],
                'WIS' => $expectedScores['WIS']['score'],
                'CHA' => $expectedScores['CHA']['score'],
            ],
            [
                'STR' => $actualScores['STR']->getCalculatedValue(),
                'DEX' => $actualScores['DEX']->getCalculatedValue(),
                'CON' => $actualScores['CON']->getCalculatedValue(),
                'INT' => $actualScores['INT']->getCalculatedValue(),
                'WIS' => $actualScores['WIS']->getCalculatedValue(),
                'CHA' => $actualScores['CHA']->getCalculatedValue(),
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
                'CHA' => $expectedScores['CHA']['modifier'],
            ],
            [
                'STR' => $actualScores['STR']->getCalculatedModifier(),
                'DEX' => $actualScores['DEX']->getCalculatedModifier(),
                'CON' => $actualScores['CON']->getCalculatedModifier(),
                'INT' => $actualScores['INT']->getCalculatedModifier(),
                'WIS' => $actualScores['WIS']->getCalculatedModifier(),
                'CHA' => $actualScores['CHA']->getCalculatedModifier(),
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
                'CHA' => $expectedScores['CHA']['savingThrowProficient'],
            ],
            [
                'STR' => $actualScores['STR']->isSavingThrowProficient(),
                'DEX' => $actualScores['DEX']->isSavingThrowProficient(),
                'CON' => $actualScores['CON']->isSavingThrowProficient(),
                'INT' => $actualScores['INT']->isSavingThrowProficient(),
                'WIS' => $actualScores['WIS']->isSavingThrowProficient(),
                'CHA' => $actualScores['CHA']->isSavingThrowProficient(),
            ],
            'Ability saving throw proficiencies'
        );
    }

    private function assertCharacterArmorClass(int $expectedArmorClass, ?CharacterArmorClass $actualArmorClass): void
    {
        $this->assertInstanceOf(CharacterArmorClass::class, $actualArmorClass);
        $this->assertSame($expectedArmorClass, $actualArmorClass->getCalculatedValue(), 'Armor Class');
    }

    private function assertCharacterHealth(int $expectedHealth, ?CharacterHealth $actualHealth): void
    {
        $this->assertInstanceOf(CharacterHealth::class, $actualHealth);
        $this->assertSame($expectedHealth, $actualHealth->getMaxHitPoints(), 'Maximum HP');
    }

    /**
     * @param array<string, int>               $expectedMovementSpeeds
     * @param array<string, CharacterMovement> $actualMovementSpeeds
     */
    private function assertCharacterMovementSpeeds(array $expectedMovementSpeeds, array $actualMovementSpeeds): void
    {
        $this->assertContainsOnlyInstancesOf(CharacterMovement::class, $actualMovementSpeeds);
        $this->assertSame(
            \json_encode($expectedMovementSpeeds),
            \json_encode($actualMovementSpeeds),
            'Movement speeds'
        );
    }

    /**
     * @param array<string, array<int, array{name: string, expertise: bool}>> $expectedProficiencies
     * @param array<string, array<int, CharacterProficiency>>                 $actualProficiencies
     */
    private function assertCharacterProficiencies(
        array $expectedProficiencies,
        array $actualProficiencies
    ): void {
        $this->assertContainsOnly('array', $actualProficiencies, true, 'Proficiencies');
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['abilities'],
            'Abilities proficiencies'
        );
        $this->assertSame(
            $expectedProficiencies['abilities'],
            array_map(fn ($a) => $a->jsonSerialize(), $actualProficiencies['abilities']),
            'Abilities proficiencies match expected list'
        );

        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['armor'],
            'Armor proficiencies'
        );
        $this->assertSame(
            $expectedProficiencies['armor'],
            array_map(fn ($a) => $a->jsonSerialize(), $actualProficiencies['armor']),
            'Armor proficiencies match expected list'
        );

        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['languages'],
            'Languages proficiencies'
        );
        $this->assertSame(
            $expectedProficiencies['languages'],
            array_map(fn ($a) => $a->jsonSerialize(), $actualProficiencies['languages']),
            'Languages proficiencies match expected list'
        );

        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['tools'],
            'Tools proficiencies'
        );
        $this->assertSame(
            $expectedProficiencies['tools'],
            array_map(fn ($a) => $a->jsonSerialize(), $actualProficiencies['tools']),
            'Tools proficiencies match expected list'
        );

        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['weapons'],
            'Weapons proficiencies'
        );
        $this->assertSame(
            $expectedProficiencies['weapons'],
            array_map(fn ($a) => $a->jsonSerialize(), $actualProficiencies['weapons']),
            'Weapons proficiencies match expected list'
        );
    }
}
