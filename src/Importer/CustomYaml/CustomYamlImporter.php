<?php

namespace loyen\DndbCharacterSheet\Importer\CustomYaml;

use loyen\DndbCharacterSheet\Exception\CharacterInvalidImportException;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Exception\CharacterYamlDataException;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlCharacter;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlFeature;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlFeatureMovementImprovement;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlFeatureProficiencyImprovement;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlProficiencyCategory;
use loyen\DndbCharacterSheet\Importer\CustomYaml\Model\YamlSource;
use loyen\DndbCharacterSheet\Importer\ImporterInterface;
use loyen\DndbCharacterSheet\Model\AbilityType;
use loyen\DndbCharacterSheet\Model\ArmorType;
use loyen\DndbCharacterSheet\Model\Character;
use loyen\DndbCharacterSheet\Model\CharacterAbility;
use loyen\DndbCharacterSheet\Model\CharacterArmorClass;
use loyen\DndbCharacterSheet\Model\CharacterClass;
use loyen\DndbCharacterSheet\Model\CharacterFeature;
use loyen\DndbCharacterSheet\Model\CharacterHealth;
use loyen\DndbCharacterSheet\Model\CharacterMovement;
use loyen\DndbCharacterSheet\Model\CurrencyType;
use loyen\DndbCharacterSheet\Model\Item;
use loyen\DndbCharacterSheet\Model\MovementType;
use loyen\DndbCharacterSheet\Model\SourceMaterial;

class CustomYamlImporter implements ImporterInterface
{
    private readonly YamlCharacter $characterData;
    private Character $character;

    public static function import(string $inputString): Character
    {
        return (new self($inputString))->createCharacter();
    }

    public function __construct(string $inputString)
    {
        $this->characterData = YamlCharacter::fromYaml($inputString)
            ?? throw new CharacterInvalidImportException();
    }

    public function createCharacter(): Character
    {
        $this->character = new Character();
        $this->character->setName($this->characterData->name);
        $this->character->setInventory($this->getInventory());
        $this->character->setClasses($this->getClasses());
        $this->character->setLevel($this->getLevel());
        $this->character->setAbilityScores($this->getAbilityScores());
        $this->character->setHealth($this->getHealth());
        $this->character->setArmorClass($this->getArmorClass());
        $this->character->setMovementSpeeds($this->getMovementSpeeds());
        $this->character->setCurrencies($this->getCurrencies());
        $this->character->setProficiencyBonus($this->getProficiencyBonus());
        $this->character->setProficiencies([
            'abilities' => [],
            'armor' => [],
            'languages' => [],
            'tools' => [],
            'weapons' => [],
        ]);

        return $this->character;
    }

    /** @return array<string, int> */
    private function getAbilityScoreImprovements(): array
    {
        return array_count_values(array_merge(...array_column(
            [
                ...$this->characterData->race->features,
                ...$this->characterData->background->features,
                ...array_column($this->characterData->classes, 'features')[0],
            ],
            'abilities'
        )));
    }

    /** @return array<string, CharacterAbility> */
    private function getAbilityScores(): array
    {
        $abilityList = [];

        $abilityTypes = array_column(AbilityType::cases(), null, 'name');

        $proficiencyList = $this->getProficiencies();
        $abilityScoreImprovements = $this->getAbilityScoreImprovements();

        foreach ($this->characterData->abilityScores as $type => $score) {
            if (!isset($abilityTypes[$type])) {
                throw new CharacterInvalidImportException('Ability score ' . $type . ' not found');
            }

            $ability = new CharacterAbility($abilityTypes[$type]);
            $ability->setValue($score);

            $ability->setSavingThrowProficient(
                \in_array($type, $proficiencyList[YamlProficiencyCategory::SavingThrows->value])
            );

            if (isset($abilityScoreImprovements[$type])) {
                $ability->setModifiers([$abilityScoreImprovements[$type]]);
            }

            $abilityList[$ability->getType()->name] = $ability;
        }

        return $abilityList;
    }

    private function getArmorClass(): CharacterArmorClass
    {
        $armorClass = new CharacterArmorClass();

        foreach ($this->character->getInventory() as $item) {
            $itemFullyEquipped = $item->isEquipped() && (!$item->canBeAttuned() || $item->isAttuned());

            if (!$itemFullyEquipped) {
                continue;
            }

            if ($item->getType() === 'armor') {
                $armorClass->setArmor($item);
            } elseif ($item->getType() === 'shield') {
                $armorClass->setModifiers([$item->getArmorClass() ?? 0]);
            }
        }

        if (empty($armorClass->getAbilityScores())) {
            $armorClass->addAbilityScore(
                $this->character->getAbilityScores()[AbilityType::DEX->name]
            );
        }

        return $armorClass;
    }

    /** @return array<int, CharacterClass> */
    private function getClasses(): array
    {
        $classList = [];

        foreach ($this->characterData->classes as $yamlClass) {
            $class = new CharacterClass($yamlClass->name);

            $sourceList = $this->createSourceList($yamlClass->sources);

            $class->setLevel($yamlClass->level);

            $featList = [];
            foreach ($yamlClass->features as $featData) {
                $featList[] = new CharacterFeature(
                    $featData->name ?? 'Unknown',
                    $featData->description,
                    $sourceList
                );
            }

            $class->setFeatures($featList);

            $classList[] = $class;
        }

        return $classList;
    }

    /**
     * @return array<string, int>
     */
    public function getCurrencies(): array
    {
        $currencies = $this->characterData->wallet;

        $currencyList = [];
        foreach (CurrencyType::cases() as $currency) {
            $currencyList[$currency->value] = $currencies[$currency->value] ?? 0;
        }

        return $currencyList;
    }

    private function getHealth(): CharacterHealth
    {
        $hitPoints = $this->characterData->classes[0]->hitPoints['firstLevel'] ?? 0;
        $hitPointsPerLevelAfterFirst = $this->characterData->classes[0]->hitPoints['higherLevel'];
        $conModifier = $this->character->getAbilityScores()[AbilityType::CON->name]->getCalculatedModifier();

        $hitPoints += $conModifier;
        $hitPoints += ($this->getLevel() - 1) * ($conModifier + $hitPointsPerLevelAfterFirst);

        return new CharacterHealth($hitPoints);
    }

    /** @return array<int, Item> */
    private function getInventory(): array
    {
        $itemList = [];

        foreach ($this->characterData->inventory as $storage) {
            foreach ($storage['items'] as $itemData) {
                $item = new Item($itemData['name'], $itemData['type'] ?? null);

                $item->setQuantity($itemData['quantity'] ?? 1);

                if (isset($itemData['damage'])) {
                    $item->setDamage($itemData['damage']['value']);
                    $item->setDamageType($itemData['damage']['type']);
                }

                if (
                    isset(
                        $itemData['armor'],
                        $itemData['armor']['class'],
                        $itemData['armor']['type']
                    )
                ) {
                    $armorType = match ($itemData['armor']['type']) {
                        'light' => ArmorType::LightArmor,
                        'medium' => ArmorType::MediumArmor,
                        'heavy' => ArmorType::HeavyArmor,
                        'shield' => ArmorType::Shield,
                        default => throw new CharacterInvalidImportException('Unknown armor type from item')
                    };

                    $item->setArmorClass($itemData['armor']['class']);
                    $item->setArmorType($armorType);
                }

                $item->setIsEquipped($itemData['equipped'] ?? false);
                $item->setIsMagical($itemData['magical'] ?? false);
                $item->setIsConsumable($itemData['consumable'] ?? false);
                $item->setIsAttuned($itemData['attuned'] ?? false);

                $itemList[] = $item;
            }
        }

        return $itemList;
    }

    private function getLevel(): int
    {
        return (int) array_sum(array_column($this->characterData->classes, 'level'));
    }

    /**
     * @return array<string, CharacterMovement>
     */
    public function getMovementSpeeds(): array
    {
        $movementSpeedList = [];

        /** @var YamlFeatureMovementImprovement[] */
        $movementFeats = array_filter(
            $this->getFeatures(),
            fn(YamlFeature $f) => $f instanceof YamlFeatureMovementImprovement
        );

        $modifiersByType = [];

        foreach (MovementType::cases() as $movementType) {
            if (empty($this->characterData->race->movement->{$movementType->value})) {
                continue;
            }

            $modifiersByType[$movementType->value] = [];

            foreach ($movementFeats as $feat) {
                $modifiersByType[$movementType->value][] = $feat->movement->{$movementType->value};
            }

            $movementSpeedList[$movementType->value] = new CharacterMovement(
                $movementType,
                $this->characterData->race->movement->{$movementType->value},
                $modifiersByType[$movementType->value]
            );
        }

        return $movementSpeedList;
    }

    /** @return YamlFeature[] */
    private function getFeatures(): array
    {
        $features = [];

        foreach ($this->characterData->background->features as $feat) {
            $features[] = $feat;
        }

        foreach ($this->characterData->race->features as $feat) {
            $features[] = $feat;
        }

        foreach ($this->characterData->classes as $class) {
            foreach ($class->features as $feat) {
                $features[] = $feat;
            }
        }

        return $features;
    }

    /** @return array<value-of<YamlProficiencyCategory>, string[]> */
    private function getProficiencies(): array
    {
        $proficiencyList = array_fill_keys(
            array_keys(array_column(YamlProficiencyCategory::cases(), null, 'value')),
            []
        );

        foreach ($this->getFeatures() as $feat) {
            if ($feat instanceof YamlFeatureProficiencyImprovement) {
                $proficiencyList[$feat->category->value] = array_merge(
                    $proficiencyList[$feat->category->value] ?? [],
                    $feat->proficiencies
                );
            }
        }

        return $proficiencyList;
    }

    public function getProficiencyBonus(): int
    {
        $level = $this->character->getLevel();

        return match (true) {
            $level <= 4 => 2,
            $level <= 8 => 3,
            $level <= 12 => 4,
            $level <= 16 => 5,
            $level <= 20 => 6,
            default => throw new CharacterYamlDataException('Level out of scope')
        };
    }

    /**
     * @param YamlSource[] $sources
     *
     * @return array<int, SourceMaterial>
     **/
    private function createSourceList(array $sources): array
    {
        $sourceList = [];

        foreach ($sources as $sourceData) {
            $sourceList[] = new SourceMaterial(
                $sourceData->name,
                $sourceData->extra ?? null
            );
        }

        return $sourceList;
    }
}
