<?php

namespace loyen\DndbCharacterSheet;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use loyen\DndbCharacterSheet\Exception\CharacterAPIException;
use loyen\DndbCharacterSheet\Exception\CharacterException;
use loyen\DndbCharacterSheet\Exception\CharacterFileReadException;
use loyen\DndbCharacterSheet\Exception\CharacterInvalidImportException;
use loyen\DndbCharacterSheet\Model\AbilityType;
use loyen\DndbCharacterSheet\Model\ArmorType;
use loyen\DndbCharacterSheet\Model\BonusType;
use loyen\DndbCharacterSheet\Model\Character;
use loyen\DndbCharacterSheet\Model\CharacterAbility;
use loyen\DndbCharacterSheet\Model\CharacterArmorClass;
use loyen\DndbCharacterSheet\Model\CharacterClass;
use loyen\DndbCharacterSheet\Model\CharacterHealth;
use loyen\DndbCharacterSheet\Model\CharacterMovement;
use loyen\DndbCharacterSheet\Model\CharacterProficiency;
use loyen\DndbCharacterSheet\Model\CurrencyType;
use loyen\DndbCharacterSheet\Model\DnDBeyond\ApiCharacter;
use loyen\DndbCharacterSheet\Model\DnDBeyond\ApiModifier;
use loyen\DndbCharacterSheet\Model\Item;
use loyen\DndbCharacterSheet\Model\MovementType;
use loyen\DndbCharacterSheet\Model\ProficiencyType;

class Importer
{
    private ApiCharacter $apiCharacter;
    /** @var array<int, ApiModifier> */
    private array $modifiers;
    private Character $character;

    public static function importFromApiById(int $characterId): Character
    {
        try {
            $client = new Client([
                'base_uri'  => 'https://character-service.dndbeyond.com/',
                'timeout'   => 4
            ]);

            $response = $client->request('GET', 'character/v5/character/' . $characterId);

            return (new self((string) $response->getBody()))->createCharacter();
        } catch (GuzzleException $e) {
            throw new CharacterAPIException('Could not get a response from DNDBeyond character API. Message: ' . $e->getMessage());
        }
    }

    public static function importFromFile(string $filePath): Character
    {
        $characterFileContent = \file_get_contents($filePath);
        if (!$characterFileContent) {
            throw new CharacterFileReadException($filePath);
        }

        return self::importFromJson($characterFileContent);
    }

    public static function importFromJson(string $jsonString): Character
    {
        return (new self($jsonString))->createCharacter();
    }

    public function __construct(string $jsonString)
    {
        $this->apiCharacter = ApiCharacter::fromApi($jsonString) ?? throw new CharacterInvalidImportException();

        $modifiers = $this->apiCharacter->modifiers;

        unset($modifiers['item']);

        $this->modifiers = \array_merge(...\array_values($modifiers));
    }

    public function createCharacter(): Character
    {
        $this->character = new Character();

        $this->character->setName($this->apiCharacter->name);
        $this->character->setInventory($this->getInventory());
        $this->character->setAbilityScores($this->getAbilityScores());
        $this->character->setArmorClass($this->getArmorClass());
        $this->character->setClasses($this->getClasses());
        $this->character->setFeatures($this->getFeatures());
        $this->character->setLevel($this->getLevel());
        $this->character->setCurrencies($this->getCurrencies());
        $this->character->setHealth($this->getHealth());
        $this->character->setProficiencyBonus($this->getProficiencyBonus());
        $this->character->setMovementSpeeds($this->getMovementSpeeds());
        $this->character->setProficiencies([
            'abilities' => $this->getAbilityProficiencies(),
            'armor'     => $this->getArmorProficiencies(),
            'languages' => $this->getLanguages(),
            'tools'     => $this->getToolProficiencies(),
            'weapons'   => $this->getWeaponProficiences(),
        ]);

        return $this->character;
    }

    /**
     * @return array<string, CharacterAbility>
     */
    public function getAbilityScores(): array
    {
        /** @var array<int, int> */
        $modifierList = [];
        $savingThrowsProficiencies = [];
        $acceptedComponentTypeIds = [
            1960452172, // origin,
            306912077, // racial
            12168134, // ability-score
            1088085227, // squat nimbleness
        ];

        foreach ($this->modifiers as $m) {
            if (
                is_int($m->value)
                && $m->entityTypeId === 1472902489
                && \in_array(
                    $m->componentTypeId,
                    $acceptedComponentTypeIds,
                    true
                )
            ) {
                $modifierList[$m->entityId][] = $m->value;
            } elseif (
                $m->type === 'proficiency'
                && \str_ends_with($m->subType, '-saving-throws')
            ) {
                $savingThrowCode = $m->subType;
                $savingThrowsProficiencies[$savingThrowCode] = $m->type;
            }
        }

        foreach ($this->apiCharacter->bonusStats as $bonusStat) {
            if (is_int($bonusStat->value)) {
                $modifierList[$bonusStat->id][] = $bonusStat->value;
            }
        }

        $overrideList = [];
        foreach ($this->apiCharacter->overrideStats as $overrideStat) {
            if (!empty($overrideStat->value)) {
                $overrideList[$overrideStat->id] = $overrideStat->value;
            }
        }

        foreach ($this->getItemModifiers() as $itemModifier) {
            $entityId = $itemModifier->entityId;
            if (
                is_int($itemModifier->value)
                && $itemModifier->modifierTypeId === BonusType::SET->value
            ) {
                $overrideList[$entityId] = $itemModifier->value;
            } else {
                $modifierList[$entityId][] = $itemModifier->value;
            }
        }

        $statsCollection = [];
        foreach ($this->apiCharacter->stats as $stat) {
            $characterAbilityType = AbilityType::from($stat->id);
            $savingThrowCode = \strtolower($characterAbilityType->name()) . '-saving-throws';

            $ability = new CharacterAbility($characterAbilityType);
            $ability->setValue($stat->value);
            $ability->setSavingThrowProficient(isset($savingThrowsProficiencies[$savingThrowCode]));

            if (isset($modifierList[$stat->id])) {
                $ability->setModifiers($modifierList[$stat->id]);
            }

            if (isset($overrideList[$stat->id])) {
                $ability->setOverrideValue($overrideList[$stat->id]);
            }

            $statsCollection[$characterAbilityType->name] = $ability;
        }

        return $statsCollection;
    }

    /**
     * @return array<int, CharacterProficiency>
     */
    public function getAbilityProficiencies(): array
    {
        return $this->getProficienciesByFilter(
            fn (ApiModifier $m) => $m->entityTypeId !== ProficiencyType::ABILITY->value
        );
    }

    public function getArmorClass(): CharacterArmorClass
    {
        $armorClass = new CharacterArmorClass();

        $armorBonuses = [];
        $itemModifiers = $this->getItemModifiers();
        foreach ($this->character->getInventory() as $item) {
            $itemFullyEquipped = $item->isEquipped() && (!$item->canBeAttuned() || $item->isAttuned());
            if (!$itemFullyEquipped) {
                continue;
            }

            $wearableArmorTypeIds = [
                ArmorType::LightArmor->value,
                ArmorType::MediumArmor->value,
                ArmorType::HeavyArmor->value,
            ];

            if (\in_array($item->getArmorTypeId(), $wearableArmorTypeIds, true)) {
                $armorClass->setArmor($item);
            } elseif ($item->getArmorClass() !== null) {
                $armorBonuses[$item->getId()] = $item->getArmorClass();
            }

            foreach ($item->getModifierIds() as $modifierId) {
                if (!isset($itemModifiers[$modifierId])) {
                    continue;
                }

                $m = $itemModifiers[$modifierId];

                if (
                    $m->type === 'bonus'
                    && (
                        $m->subType === 'armor-class'
                        || $m->subType === 'armored-armor-class'
                    )
                    && $m->isGranted === true
                ) {
                    $armorBonuses[] = \intval($itemModifiers[$modifierId]->value);
                }
            }
        }

        foreach ($this->modifiers as $modifierId => $m) {
            $isArmored = $m->type === 'bonus'
                && \in_array(
                    $m->subType,
                    [
                        'armored-armor-class',
                        'armor-class'
                    ],
                    true
                )
                && $m->modifierTypeId === BonusType::BONUS->value
                && $m->modifierSubTypeId !== 1;

            $isUnarmored = $m->type === 'set'
                && $m->subType === 'unarmored-armor-class'
                && $m->modifierTypeId === BonusType::SET->value
                && $m->modifierSubTypeId === 1006;

            if (!$isArmored && !$isUnarmored) {
                continue;
            }

            if (!$armorClass->isWearingArmor()) {
                /**
                 * Natural Armor = CON instead of DEX.
                 * Unarmored Defense = DEX + WIS or DEX + CON.
                 */
                if ($m->componentId === 571068) {
                    $armorClass->addAbilityScore(
                        $this->character->getAbilityScores()[AbilityType::CON->name]
                    );
                } elseif ($m->componentId === 226) {
                    $armorClass->addAbilityScore(
                        $this->character->getAbilityScores()[AbilityType::DEX->name]
                    );
                    $armorClass->addAbilityScore(
                        $this->character->getAbilityScores()[AbilityType::WIS->name]
                    );
                } elseif ($m->componentId === 52) {
                    $armorClass->addAbilityScore(
                        $this->character->getAbilityScores()[AbilityType::DEX->name]
                    );
                    $armorClass->addAbilityScore(
                        $this->character->getAbilityScores()[AbilityType::CON->name]
                    );
                }
            } elseif (
                $m->value !== null
                && $m->subType !== 'unarmored-armor-class'
            ) {
                $armorBonuses[] = \intval($m->value);
            }
        }

        $armorClass->setModifiers($armorBonuses);

        if (empty($armorClass->getAbilityScores())) {
            $armorClass->addAbilityScore(
                $this->character->getAbilityScores()[AbilityType::DEX->name]
            );
        }

        return $armorClass;
    }

    /**
     * @return array<int, CharacterProficiency>
     */
    public function getArmorProficiencies(): array
    {
        return $this->getProficienciesByFilter(
            fn (ApiModifier $m) => $m->entityTypeId !== ProficiencyType::ARMOR->value
        );
    }

    /**
     * @return array<int, CharacterClass>
     */
    public function getClasses(): array
    {
        $classes = $this->apiCharacter->classes;
        $classOptions = \array_column($this->apiCharacter->options['class'], null, 'componentId');

        // Do not include any of these in the features list
        $skippedFeatures = [
            'Ability Score Improvement',
            'Hit Points',
            'Proficiencies',
            'Fast Movement'
        ];

        $classList = [];
        foreach ($classes as $class) {
            $characterClass = new CharacterClass($class->definition->name);
            $characterClass->setLevel($class->level);

            $classFeatures = $class->definition->classFeatures;

            if (isset($class->subclassDefinition)) {
                $characterClass->setSubName($class->subclassDefinition->name);

                $classFeatures = \array_merge($classFeatures, $class->subclassDefinition->classFeatures);
            }

            foreach ($classFeatures as $feature) {
                $featureName = isset($classOptions[$feature->id]->definition->name)
                    ? $feature->name . ' - ' . $classOptions[$feature->id]->definition->name
                    : $feature->name;

                if (
                    \in_array($featureName, $characterClass->getFeatures(), true)
                    || $feature->requiredLevel > $class->level
                    || \in_array($feature->name, $skippedFeatures, true)
                ) {
                    continue;
                }

                $characterClass->addFeature($featureName);
            }

            $classList[] = $characterClass;
        }

        return $classList;
    }

    /**
     * @return array<string, int>
     */
    public function getCurrencies(): array
    {
        $currencies = $this->apiCharacter->currencies;

        $currencyList = [];
        foreach (CurrencyType::cases() as $currency) {
            $currencyList[$currency->value] = $currencies[$currency->value];
        }

        return $currencyList;
    }

    public function getHealth(): CharacterHealth
    {
        $baseHitPoints = (int) $this->apiCharacter->baseHitPoints;

        $healthModifiers = [];
        if (isset($this->apiCharacter->bonusHitPoints)) {
            $healthModifiers[] = $this->apiCharacter->bonusHitPoints;
        }
        if (isset($this->apiCharacter->removedHitPoints)) {
            $healthModifiers[] = -$this->apiCharacter->removedHitPoints;
        }

        $constituionScore = $this->character->getAbilityScores()[AbilityType::CON->name];
        $baseHitPoints += (int) \floor($this->character->getLevel() * $constituionScore->getCalculatedModifier());

        return new CharacterHealth(
            $baseHitPoints,
            $healthModifiers,
            $this->apiCharacter->temporaryHitPoints ?? 0,
            $this->apiCharacter->overrideHitPoints ?? null,
        );
    }

    /**
     * @return array<int, string>
     */
    public function getFeatures(): array
    {
        $feats = $this->apiCharacter->feats;

        $featureList = [];
        foreach ($feats as $feat) {
            if (\in_array($feat->definition->name, $featureList, true)) {
                continue;
            }

            $featureList[] = $feat->definition->name;
        }

        return $featureList;
    }

    /**
     * @return array<int, Item>
     */
    public function getInventory(): array
    {
        $itemList = [];
        foreach ($this->apiCharacter->inventory as $apiItem) {
            $apiItemDefinition = $apiItem->definition;
            $item = new Item(
                $apiItemDefinition->name,
                $apiItemDefinition->filterType
            );
            $item->setId($apiItemDefinition->id);
            $item->setTypeId($apiItemDefinition->entityTypeId);

            $subType = $apiItemDefinition->subType ?? $apiItemDefinition->type;

            if ($apiItemDefinition->filterType !== $subType) {
                $item->setSubType($subType);
            }

            $item->setQuantity($apiItem->quantity);
            $item->setCanAttune($apiItemDefinition->canAttune);
            $item->setIsAttuned($apiItem->isAttuned);
            $item->setIsConsumable($apiItemDefinition->isConsumable);
            $item->setIsEquipped($apiItemDefinition->canEquip && $apiItem->equipped);
            $item->setIsMagical($apiItemDefinition->magic);

            if (isset($apiItemDefinition->armorClass)) {
                $item->setArmorClass($apiItemDefinition->armorClass);
            }

            if (isset($apiItemDefinition->armorTypeId)) {
                $item->setArmorTypeId($apiItemDefinition->armorTypeId);
            }

            if (isset($apiItemDefinition->damageType)) {
                $item->setDamageType($apiItemDefinition->damageType);
            }

            if (isset($apiItemDefinition->damage?->diceString)) {
                $item->setDamage($apiItemDefinition->damage->diceString);
            }

            if (isset($apiItemDefinition->range)) {
                $item->setRange($apiItemDefinition->range);
            }

            if (isset($apiItemDefinition->longRange)) {
                $item->setLongRange($apiItemDefinition->longRange);
            }

            if (isset($apiItemDefinition->properties)) {
                foreach ($apiItemDefinition->properties as $p) {
                    $item->addProperty($p->name);
                }
            }

            if (isset($apiItemDefinition->grantedModifiers)) {
                $item->setModifierIds(\array_values(\array_unique(\array_column(
                    $apiItemDefinition->grantedModifiers,
                    'id'
                ))));
            }

            $itemList[] = $item;
        }

        return $itemList;
    }

    /**
     * @return array<int, CharacterProficiency>
     */
    public function getLanguages(): array
    {
        return $this->getProficienciesByFilter(
            fn (ApiModifier $m) => $m->entityTypeId !== 906033267
        );
    }

    public function getLevel(): int
    {
        return (int) \min(20, \array_sum(\array_column($this->apiCharacter->classes, 'level')));
    }

    /**
     * @return array<int, ApiModifier>
     */
    public function getItemModifiers(): array
    {
        $itemModifiers = \array_column($this->apiCharacter->modifiers['item'], null, 'id');

        $itemModifierList = [];
        foreach ($this->character->getInventory() as $item) {
            $applyModifier = $item->isEquipped() && (!$item->canBeAttuned() || $item->isAttuned());
            if (!$applyModifier) {
                continue;
            }

            foreach ($item->getModifierIds() as $modifierId) {
                if (!isset($itemModifiers[$modifierId])) {
                    continue;
                }
                $itemModifierList[$modifierId] = $itemModifiers[$modifierId];
            }
        }

        return $itemModifierList;
    }

    /**
     * @return array<string, CharacterMovement>
     */
    public function getMovementSpeeds(): array
    {
        $walkingSpeed = $this->apiCharacter->race->weightSpeeds['normal']['walk'] ?? 0;

        $walkingSpeedModifierSubTypes = [
            1685, // unarmored-movement
            1697, // speed-walking (ex. Squat Nimbleness)
            40,   // mobile feat
        ];

        /** @var array<int, int> */
        $walkingModifiers = \array_column(
            \array_filter(
                $this->modifiers,
                fn (ApiModifier $m) => is_int($m->value)
                    && $m->modifierTypeId === BonusType::BONUS->value
                    && \in_array($m->modifierSubTypeId, $walkingSpeedModifierSubTypes, true)
            ),
            'value'
        );

        $speedCollection = [
            MovementType::WALK->name() => new CharacterMovement(
                MovementType::WALK,
                $walkingSpeed,
                $walkingModifiers
            )
        ];

        $flyingModifiers = \array_filter(
            $this->modifiers,
            fn (ApiModifier $m) => $m->modifierTypeId === BonusType::SET->value && 182 === $m->modifierSubTypeId
        );

        if (!empty($flyingModifiers)) {
            $flyingSpeed = \max(\array_column($flyingModifiers, 'value'));

            $speedCollection[MovementType::FLY->name()] = new CharacterMovement(
                MovementType::FLY,
                $flyingSpeed ?: $walkingSpeed,
                $flyingSpeed ? [ 0 ] : $walkingModifiers
            );
        }

        return $speedCollection;
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
            default => throw new CharacterException('Level out of scope')
        };
    }

    /**
     * @return array<int, CharacterProficiency>
     */
    public function getProficienciesByFilter(callable $function): array
    {
        $proficiencies = [];
        foreach ($this->modifiers as $m) {
            if (
                $m->entityTypeId === null
                || isset($proficiencies[$m->entityId])
                || $function($m)
            ) {
                continue;
            }

            $proficiencies[$m->entityId] = new CharacterProficiency(
                ProficiencyType::from($m->entityTypeId),
                $m->friendlySubtypeName,
                $m->type === 'expertise'
            );
        }

        \uasort($proficiencies, fn ($a, $b) => $a->name <=> $b->name);

        return \array_values($proficiencies);
    }

    /**
     * @return array<int, CharacterProficiency>
     */
    public function getToolProficiencies(): array
    {
        return $this->getProficienciesByFilter(
            fn (ApiModifier $m) => $m->entityTypeId !== ProficiencyType::TOOL->value
        );
    }

    /**
     * @return array<int, CharacterProficiency>
     */
    public function getWeaponProficiences(): array
    {
        $weaponEntityIdList = [
            ProficiencyType::WEAPONGROUP->value,
            ProficiencyType::WEAPON->value,
        ];

        return $this->getProficienciesByFilter(
            fn (ApiModifier $m) => !\in_array($m->entityTypeId, $weaponEntityIdList, true)
        );
    }
}
