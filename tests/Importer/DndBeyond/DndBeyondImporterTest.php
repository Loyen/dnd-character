<?php

namespace DndCharacter\Tests\Importer\DndBeyond;

use DndCharacter\Exception\CharacterInvalidImportException;
use DndCharacter\Importer\DndBeyond\DndBeyondImporter;
use DndCharacter\Importer\DndBeyond\Model\ApiBookSource;
use DndCharacter\Importer\DndBeyond\Model\ApiCharacter;
use DndCharacter\Importer\DndBeyond\Model\ApiChoice;
use DndCharacter\Importer\DndBeyond\Model\ApiClass;
use DndCharacter\Importer\DndBeyond\Model\ApiClassDefinition;
use DndCharacter\Importer\DndBeyond\Model\ApiClassDefinitionFeature;
use DndCharacter\Importer\DndBeyond\Model\ApiClassFeature;
use DndCharacter\Importer\DndBeyond\Model\ApiClassFeatureDefinition;
use DndCharacter\Importer\DndBeyond\Model\ApiCustomProficiency;
use DndCharacter\Importer\DndBeyond\Model\ApiDice;
use DndCharacter\Importer\DndBeyond\Model\ApiFeat;
use DndCharacter\Importer\DndBeyond\Model\ApiFeatDefinition;
use DndCharacter\Importer\DndBeyond\Model\ApiInventoryItem;
use DndCharacter\Importer\DndBeyond\Model\ApiInventoryItemDefinition;
use DndCharacter\Importer\DndBeyond\Model\ApiLevelScale;
use DndCharacter\Importer\DndBeyond\Model\ApiModifier;
use DndCharacter\Importer\DndBeyond\Model\ApiOption;
use DndCharacter\Importer\DndBeyond\Model\ApiOptionDefinition;
use DndCharacter\Importer\DndBeyond\Model\ApiProperty;
use DndCharacter\Importer\DndBeyond\Model\ApiRace;
use DndCharacter\Importer\DndBeyond\Model\ApiStat;
use DndCharacter\Importer\DndBeyond\Model\ApiTraits;
use DndCharacter\Importer\DndBeyond\Model\List\ApiCustomProficiencyType;
use DndCharacter\Importer\DndBeyond\Model\List\ApiMartialRangedWeaponEntityId;
use DndCharacter\Importer\DndBeyond\Model\List\ApiMartialWeaponEntityId;
use DndCharacter\Importer\DndBeyond\Model\List\ApiProficiencyGroupEntityTypeId;
use DndCharacter\Importer\DndBeyond\Model\List\ApiSimpleRangedWeaponEntityId;
use DndCharacter\Importer\DndBeyond\Model\List\ApiSimpleWeaponEntityId;
use DndCharacter\Importer\DndBeyond\Model\Source;
use DndCharacter\Model\AbilityType;
use DndCharacter\Model\Character;
use DndCharacter\Model\CharacterAbility;
use DndCharacter\Model\CharacterArmorClass;
use DndCharacter\Model\CharacterClass;
use DndCharacter\Model\CharacterFeature;
use DndCharacter\Model\CharacterHealth;
use DndCharacter\Model\CharacterMovement;
use DndCharacter\Model\CharacterProficiency;
use DndCharacter\Model\CharacterTraits;
use DndCharacter\Model\Item;
use DndCharacter\Model\SourceMaterial;
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
#[CoversClass(ApiCustomProficiency::class)]
#[CoversClass(ApiCustomProficiencyType::class)]
#[CoversClass(ApiDice::class)]
#[CoversClass(ApiFeat::class)]
#[CoversClass(ApiFeatDefinition::class)]
#[CoversClass(ApiInventoryItem::class)]
#[CoversClass(ApiInventoryItemDefinition::class)]
#[CoversClass(ApiLevelScale::class)]
#[CoversClass(ApiMartialRangedWeaponEntityId::class)]
#[CoversClass(ApiMartialWeaponEntityId::class)]
#[CoversClass(ApiModifier::class)]
#[CoversClass(ApiOption::class)]
#[CoversClass(ApiOptionDefinition::class)]
#[CoversClass(ApiProficiencyGroupEntityTypeId::class)]
#[CoversClass(ApiProperty::class)]
#[CoversClass(ApiRace::class)]
#[CoversClass(ApiSimpleRangedWeaponEntityId::class)]
#[CoversClass(ApiSimpleWeaponEntityId::class)]
#[CoversClass(ApiStat::class)]
#[CoversClass(ApiTraits::class)]
#[CoversClass(CharacterTraits::class)]
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

        foreach (glob($characterFileDir . 'character_*_expected.json') ?: [] as $filePath) {
            $characterData = json_decode(
                file_get_contents($filePath) ?: '',
                true,
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
            file_get_contents($expectedCharacterData['apiFilePath']) ?: '',
        );

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
            $character->getProficiencies(),
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
            'Ability scores',
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
            'Ability modifiers',
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
            'Ability saving throw proficiencies',
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
            json_encode($expectedMovementSpeeds),
            json_encode($actualMovementSpeeds),
            'Movement speeds',
        );
    }

    /**
     * @param array<string, array<int, array{name: string, expertise: bool}>> $expectedProficiencies
     * @param array<string, array<int, CharacterProficiency>>                 $actualProficiencies
     */
    private function assertCharacterProficiencies(
        array $expectedProficiencies,
        array $actualProficiencies,
    ): void {
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['abilities'],
            'Abilities proficiencies',
        );
        $this->assertSame(
            $expectedProficiencies['abilities'],
            array_map(static fn($a) => $a->jsonSerialize(), $actualProficiencies['abilities']),
            'Abilities proficiencies match expected list',
        );

        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['armor'],
            'Armor proficiencies',
        );
        $this->assertSame(
            $expectedProficiencies['armor'],
            array_map(static fn($a) => $a->jsonSerialize(), $actualProficiencies['armor']),
            'Armor proficiencies match expected list',
        );

        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['languages'],
            'Languages proficiencies',
        );
        $this->assertSame(
            $expectedProficiencies['languages'],
            array_map(static fn($a) => $a->jsonSerialize(), $actualProficiencies['languages']),
            'Languages proficiencies match expected list',
        );

        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['tools'],
            'Tools proficiencies',
        );
        $this->assertSame(
            $expectedProficiencies['tools'],
            array_map(static fn($a) => $a->jsonSerialize(), $actualProficiencies['tools']),
            'Tools proficiencies match expected list',
        );

        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['weapons'],
            'Weapons proficiencies',
        );
        $this->assertSame(
            $expectedProficiencies['weapons'],
            array_map(static fn($a) => $a->jsonSerialize(), $actualProficiencies['weapons']),
            'Weapons proficiencies match expected list',
        );
    }

    public function testTraits(): void
    {
        $character = DndBeyondImporter::import(
            file_get_contents(__DIR__ . '/Fixtures/character_40953316_api_response.json') ?: '',
        );

        $this->assertEquals(
            [
                'A simple, direct solution is the best path to success.',
                'I can relate to almost any combat situation.',
                'I like to eat.',
                'Gold is great.',
                'I only follow myself. I choose to follow others when I can\'t be bothered with the consequences.',
                'I have to do what is right.',
                'I have a bad temper and a short fuse.',
            ],
            $character->getTraits()->traits,
        );
    }
}
