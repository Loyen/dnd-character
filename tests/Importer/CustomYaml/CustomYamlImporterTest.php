<?php

namespace Tests\App\Importer\CustomYaml;

use DndCharacter\Exception\CharacterInvalidImportException;
use DndCharacter\Importer\CustomYaml\CustomYamlImporter;
use DndCharacter\Importer\CustomYaml\Model\YamlBackground;
use DndCharacter\Importer\CustomYaml\Model\YamlCharacter;
use DndCharacter\Importer\CustomYaml\Model\YamlClass;
use DndCharacter\Importer\CustomYaml\Model\YamlFeature;
use DndCharacter\Importer\CustomYaml\Model\YamlFeatureAbilityScoreImprovement;
use DndCharacter\Importer\CustomYaml\Model\YamlFeatureMovementImprovement;
use DndCharacter\Importer\CustomYaml\Model\YamlFeatureProficiencyImprovement;
use DndCharacter\Importer\CustomYaml\Model\YamlMovement;
use DndCharacter\Importer\CustomYaml\Model\YamlRace;
use DndCharacter\Importer\CustomYaml\Model\YamlSource;
use DndCharacter\Model\AbilityType;
use DndCharacter\Model\Character;
use DndCharacter\Model\CharacterAbility;
use DndCharacter\Model\CharacterArmorClass;
use DndCharacter\Model\CharacterClass;
use DndCharacter\Model\CharacterFeature;
use DndCharacter\Model\CharacterHealth;
use DndCharacter\Model\CharacterMovement;
use DndCharacter\Model\CharacterProficiency;
use DndCharacter\Model\Item;
use DndCharacter\Model\SourceMaterial;
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
                true,
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
            file_get_contents($expectedCharacterData['inputFilePath']) ?: '',
        );

        $this->assertSame($expectedCharacterData['name'], $character->getName());
        $this->assertSame($expectedCharacterData['level'], $character->getLevel(), 'Character Level');
        $this->assertCharacterAbilityScores($expectedCharacterData['abilityScores'], $character->getAbilityScores());
        $this->assertCharacterHealth($expectedCharacterData['health'], $character->getHealth());
        $this->assertContainsOnlyInstancesOf(CharacterClass::class, $character->getClasses());
        $this->assertCharacterMovementSpeeds($expectedCharacterData['movementSpeeds'], $character->getMovementSpeeds());
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
            (string) json_encode($expectedMovementSpeeds),
            (string) json_encode($actualMovementSpeeds),
            'Movement speeds',
        );
    }

    /**
     * @param array<string, array<int, CharacterProficiency>> $actualProficiencies
     */
    private function assertCharacterProficiencies(array $actualProficiencies): void
    {
        $this->assertContainsOnlyArray($actualProficiencies, 'Proficiencies');
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['abilities'],
            'Abilities proficiencies',
        );
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['armor'],
            'Armor proficiencies',
        );
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['languages'],
            'Languages proficiencies',
        );
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['tools'],
            'Tools proficiencies',
        );
        $this->assertContainsOnlyInstancesOf(
            CharacterProficiency::class,
            $actualProficiencies['weapons'],
            'Weapons proficiencies',
        );
    }
}
