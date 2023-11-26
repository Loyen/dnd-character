<?php

namespace Tests\loyen\DndbCharacterSheet\Importer\CustomYaml;

use loyen\DndbCharacterSheet\Exception\CharacterInvalidImportException;
use loyen\DndbCharacterSheet\Importer\CustomYaml\CustomYamlImporter;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlBackground;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlCharacter;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlClass;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlFeature;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlFeatureAbilityScoreImprovement;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlFeatureMovementImprovement;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlFeatureProficiencyImprovement;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlMovement;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlRace;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlSource;
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

#[CoversClass(Character::class)]
#[CoversClass(CustomYamlImporter::class)]
#[CoversClass(YamlBackground::class)]
#[CoversClass(YamlCharacter::class)]
#[CoversClass(YamlClass::class)]
#[CoversClass(YamlFeature::class)]
#[CoversClass(YamlFeatureAbilityScoreImprovement::class)]
#[CoversClass(YamlFeatureMovementImprovement::class)]
#[CoversClass(YamlFeatureProficiencyImprovement::class)]
#[CoversClass(YamlMovement::class)]
#[CoversClass(YamlRace::class)]
#[CoversClass(YamlSource::class)]
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
final class CustomYamlImporterTest extends TestCase
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
                true
            );

            $characterData['inputFilePath'] = $characterFileDir
                . 'character_'
                . strtolower(str_replace(' ', '_', $characterData['name']))
                . '_input.yml';

            $characterName = (string) $characterData['name'];

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
        $character = CustomYamlImporter::import(
            file_get_contents($expectedCharacterData['inputFilePath']) ?: ''
        );

        $this->assertInstanceOf(Character::class, $character);
        $this->assertSame($expectedCharacterData['name'], $character->getName());
        $this->assertSame($expectedCharacterData['level'], $character->getLevel(), 'Character Level');
        $this->assertCharacterAbilityScores($expectedCharacterData['abilityScores'], $character->getAbilityScores());
        $this->assertCharacterHealth($expectedCharacterData['health'], $character->getHealth());
        $this->assertContainsOnlyInstancesOf(CharacterClass::class, $character->getClasses());
        $this->assertContainsOnlyInstancesOf(Item::class, $character->getInventory());
        $this->assertCharacterProficiencies($character->getProficiencies());
    }

    public function testInvalidCharacterImportThrowsException(): void
    {
        $this->expectException(CharacterInvalidImportException::class);
        CustomYamlImporter::import('');
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

    private function assertCharacterHealth(int $expectedHealth, ?CharacterHealth $actualHealth): void
    {
        $this->assertInstanceOf(CharacterHealth::class, $actualHealth);
        $this->assertSame($expectedHealth, $actualHealth->getMaxHitPoints(), 'Maximum HP');
    }

    /**
     * @param array<string, array<int, CharacterProficiency>> $actualProficiencies
     */
    private function assertCharacterProficiencies(array $actualProficiencies): void
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
