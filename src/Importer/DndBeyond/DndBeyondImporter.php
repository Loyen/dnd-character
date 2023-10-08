<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond;

use loyen\DndbCharacterSheet\Exception\CharacterException;
use loyen\DndbCharacterSheet\Exception\CharacterInvalidImportException;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiCharacter;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiModifier;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\List\ApiArmorTypeComponentId;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\List\ApiBonusTypeModifierTypeId;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\List\ApiCustomProficiencyType;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\List\ApiMartialRangedWeaponEntityId;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\List\ApiMartialWeaponEntityId;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\List\ApiModifierTypeModifierTypeId;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\List\ApiProficiencyGroupEntityTypeId;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\List\ApiSimpleRangedWeaponEntityId;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\List\ApiSimpleWeaponEntityId;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\List\ApiWeaponGroupEntityId;
use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\Source;
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
use loyen\DndbCharacterSheet\Model\CharacterProficiency;
use loyen\DndbCharacterSheet\Model\CurrencyType;
use loyen\DndbCharacterSheet\Model\Item;
use loyen\DndbCharacterSheet\Model\MovementType;
use loyen\DndbCharacterSheet\Model\ProficiencyGroup;
use loyen\DndbCharacterSheet\Model\ProficiencyType;
use loyen\DndbCharacterSheet\Model\SourceMaterial;

class DndBeyondImporter implements ImporterInterface
{
    private ApiCharacter $apiCharacter;
    /** @var array<int, ApiModifier> */
    private array $modifiers;
    private Character $character;

    public static function import(string $inputString): Character
    {
        return (new self($inputString))->createCharacter();
    }

    public function __construct(string $jsonString)
    {
        $this->apiCharacter = ApiCharacter::fromApi($jsonString)
            ?? throw new CharacterInvalidImportException();

        $modifiers = $this->apiCharacter->modifiers;

        unset($modifiers['item']);

        $noChoiceSelectedComponentIds = $this->getComponentIdsThatAreMissingOptionChoice();

        $this->modifiers = \array_filter(
            \array_merge(...\array_values($modifiers)),
            fn ($m) => !\in_array($m->componentId, $noChoiceSelectedComponentIds)
        );
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
            'armor' => $this->getArmorProficiencies(),
            'languages' => $this->getLanguages(),
            'tools' => $this->getToolProficiencies(),
            'weapons' => $this->getWeaponProficiences(),
        ]);

        return $this->character;
    }

    /**
     * @return array<string, CharacterAbility>
     */
    public function getAbilityScores(): array
    {
        $modifierList = [];
        $savingThrowsProficiencies = [];

        $savingThrowComponentId = null;

        foreach ($this->modifiers as $m) {
            if (
                !empty($m->value)
                && $m->entityId !== null
                && $m->entityTypeId === 1472902489
                && AbilityType::tryFrom($m->entityId) !== null
            ) {
                $modifierList[$m->entityId] ??= [];
                $modifierList[$m->entityId][] = $m->value;
            } elseif (
                $m->modifierTypeId === 10
                && $m->componentTypeId === 12168134
                && (
                    $savingThrowComponentId === null
                    || $savingThrowComponentId === $m->componentId
                    || $m->availableToMulticlass
                )
            ) {
                $savingThrowComponentId ??= $m->componentId;
                $savingThrowCode = $m->subType;
                $savingThrowsProficiencies[$savingThrowCode] = $m->type;
            }
        }

        foreach ($this->apiCharacter->bonusStats as $bonusStat) {
            if ($bonusStat->value) {
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
            if (
                empty($itemModifier->value)
                || $itemModifier->entityId === null
                || $itemModifier->entityTypeId !== 1472902489
                || AbilityType::tryFrom($itemModifier->entityId) === null
            ) {
                continue;
            }

            if ($itemModifier->modifierTypeId === ApiBonusTypeModifierTypeId::Set->value) {
                $overrideList[$itemModifier->entityId] = $itemModifier->value;
            } else {
                $modifierList[$itemModifier->entityId][] = $itemModifier->value;
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
            fn (
                ApiModifier & $m,
                array &$proficiencyList
            ) => $m->entityTypeId !== ApiProficiencyGroupEntityTypeId::Ability->value || (
                isset($proficiencyList[$m->entityId])
                && !in_array(ApiModifierTypeModifierTypeId::tryFrom($m->modifierTypeId), [
                    ApiModifierTypeModifierTypeId::Expertise,
                    ApiModifierTypeModifierTypeId::HalfProficiency,
                    ApiModifierTypeModifierTypeId::HalfProficiencyRoundUp,
                ])
            )
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

            $wearableArmorTypes = [
                ArmorType::LightArmor,
                ArmorType::MediumArmor,
                ArmorType::HeavyArmor,
            ];

            if (\in_array($item->getArmorType(), $wearableArmorTypes, true)) {
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
                        'armor-class',
                    ],
                    true
                )
                && $m->modifierTypeId === ApiBonusTypeModifierTypeId::Bonus->value
                && $m->modifierSubTypeId !== 1;

            $isUnarmored = $m->type === 'set'
                && $m->subType === 'unarmored-armor-class'
                && $m->modifierTypeId === ApiBonusTypeModifierTypeId::Set->value
                && $m->modifierSubTypeId === 1006;

            if (!$isArmored && !$isUnarmored) {
                continue;
            }

            if (!$armorClass->isWearingArmor()) {
                if ($m->componentId === ApiArmorTypeComponentId::AutognomeArmoredCasing->value) {
                    $armorClass->setValue(13);
                    $armorClass->addAbilityScore(
                        $this->character->getAbilityScores()[AbilityType::DEX->name]
                    );
                } elseif ($m->componentId === ApiArmorTypeComponentId::LizardFolkNaturalArmor->value) {
                    $armorClass->setValue(13);
                    $armorClass->addAbilityScore(
                        $this->character->getAbilityScores()[AbilityType::DEX->name]
                    );
                } elseif ($m->componentId === ApiArmorTypeComponentId::LoxodonNaturalArmor->value) {
                    $armorClass->setValue(12);
                    $armorClass->addAbilityScore(
                        $this->character->getAbilityScores()[AbilityType::CON->name]
                    );
                } elseif ($m->componentId === ApiArmorTypeComponentId::MonkUnarmoredDefense->value) {
                    $armorClass->addAbilityScore(
                        $this->character->getAbilityScores()[AbilityType::DEX->name]
                    );
                    $armorClass->addAbilityScore(
                        $this->character->getAbilityScores()[AbilityType::WIS->name]
                    );
                } elseif ($m->componentId === ApiArmorTypeComponentId::BarbarianUnarmoredDefense->value) {
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
            fn (ApiModifier $m) => $m->entityTypeId !== ApiProficiencyGroupEntityTypeId::Armor->value
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
            'Fast Movement',
        ];

        $classList = [];
        foreach ($classes as $class) {
            $characterClass = new CharacterClass($class->definition->name);
            $characterClass->setLevel($class->level);

            $featureNameList = [];

            foreach ($class->classFeatures as $feature) {
                $featureName = isset($classOptions[$feature->definition->id]->definition->name)
                    ? $feature->definition->name . ' - ' . $classOptions[$feature->definition->id]->definition->name
                    : $feature->definition->name;

                if (
                    \in_array($featureName, $featureNameList, true)
                    || $feature->definition->requiredLevel > $class->level
                    || \in_array($feature->definition->name, $skippedFeatures, true)
                ) {
                    continue;
                }

                $sourceList = [];
                if (isset($feature->definition->sources)) {
                    foreach ($feature->definition->sources as $apiSource) {
                        $source = Source::tryFrom($apiSource->sourceId) ?? Source::UnknownSource;

                        $sourceList[] = new SourceMaterial(
                            $source->name(),
                            'pg ' . $apiSource->pageNumber
                        );
                    }
                }

                $classFeature = new CharacterFeature(
                    $featureName,
                    $feature->definition->snippet ?? '',
                    $sourceList
                );

                $characterClass->addFeature($classFeature);
                $featureNameList[] = $featureName;
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
     * @return array<int, CharacterFeature>
     */
    public function getFeatures(): array
    {
        $feats = $this->apiCharacter->feats;

        $featureList = [];
        foreach ($feats as $feat) {
            $sourceList = [];
            if (isset($feat->definition->sources)) {
                foreach ($feat->definition->sources as $apiSource) {
                    $source = Source::tryFrom($apiSource->sourceId) ?? Source::UnknownSource;

                    $sourceList[] = new SourceMaterial(
                        $source->name(),
                        'pg ' . $apiSource->pageNumber
                    );
                }
            }

            $characterFeature = new CharacterFeature(
                $feat->definition->name,
                $feat->definition->description ?? '',
                $sourceList
            );

            $featureList[] = $characterFeature;
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

            if (
                isset($apiItemDefinition->armorTypeId)
                && $this->getArmorTypeFromArmorTypeId($apiItemDefinition->armorTypeId) !== null
            ) {
                $item->setArmorType(
                    $this->getArmorTypeFromArmorTypeId($apiItemDefinition->armorTypeId)
                );
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
        $languages = $this->getProficienciesByFilter(
            fn (ApiModifier $m) => $m->entityTypeId !== ApiProficiencyGroupEntityTypeId::Language->value
        );

        if (isset($this->apiCharacter->customProficiencies)) {
            foreach ($this->apiCharacter->customProficiencies as $customProficiency) {
                if ($customProficiency->type === ApiCustomProficiencyType::Language->value) {
                    $languages[] = new CharacterProficiency(
                        ProficiencyGroup::Language,
                        $customProficiency->name,
                        ProficiencyType::Proficient
                    );
                }
            }

            \uasort($languages, fn ($a, $b) => $a->name <=> $b->name);
        }

        return $languages;
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
        $movementSpeeds = $this->apiCharacter->race->weightSpeeds['normal'];
        $movementModifiers = [];

        $walkingSpeedModifierSubTypes = [
            1685, // unarmored-movement
            1697, // speed-walking (ex. Squat Nimbleness)
            40,   // mobile feat
        ];

        foreach ($this->modifiers as $m) {
            if (
                $m->modifierTypeId === ApiBonusTypeModifierTypeId::Bonus->value
                && $m->value !== null
            ) {
                if (\in_array($m->modifierSubTypeId, $walkingSpeedModifierSubTypes, true)) {
                    $movementModifiers[MovementType::WALK->value] ??= [];
                    $movementModifiers[MovementType::WALK->value][] = $m->value;
                }
            } elseif ($m->modifierTypeId === ApiBonusTypeModifierTypeId::Set->value) {
                if ($m->modifierSubTypeId === 181) { // innate-speed-walking
                    $movementSpeeds[MovementType::WALK->value] = $m->value;
                } elseif ($m->modifierSubTypeId === 182) { // innate-speed-flying
                    $movementSpeeds[MovementType::FLY->value] = $m->value;
                }
            }
        }

        $speedCollection = [];

        foreach (MovementType::cases() as $movementType) {
            if (
                $movementSpeeds[$movementType->value] === 0
                && empty($movementModifiers[$movementType->value])
            ) {
                continue;
            }

            if ($movementSpeeds[$movementType->value] === null) {
                $speedCollection[$movementType->value] = &$speedCollection[MovementType::WALK->value];
            } else {
                $speedCollection[$movementType->value] = new CharacterMovement(
                    $movementType,
                    $movementSpeeds[$movementType->value],
                    $movementModifiers[$movementType->value] ?? []
                );
            }
        }

        return $speedCollection;
    }

    /** @return array<int, int> */
    public function getComponentIdsThatAreMissingOptionChoice(): array
    {
        $missingOptionList = [];

        $optionList = \array_merge(...\array_values($this->apiCharacter->options));
        $choiceList = \array_column(
            \array_merge(...\array_values($this->apiCharacter->choices)),
            'componentId'
        );

        foreach ($optionList as $option) {
            if (\in_array($option->componentId, $choiceList)) {
                continue;
            }

            $missingOptionList[] = $option->definition->id;
        }

        return $missingOptionList;
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
                || $function($m, $proficiencies)
                || !$this->isAvailableDuringMultiClass($m)
            ) {
                continue;
            }

            $proficiencies[$m->entityId] = new CharacterProficiency(
                ApiProficiencyGroupEntityTypeId::from($m->entityTypeId)->toProficiencyGroup(),
                !empty($m->restriction)
                    ? $m->friendlySubtypeName . ' (' . $m->restriction . ')'
                    : $m->friendlySubtypeName,
                match ($m->type) {
                    'proficiency' => ProficiencyType::Proficient,
                    'expertise' => ProficiencyType::Expertise,
                    'half-proficiency',
                    'half-proficiency-round-up' => ProficiencyType::HalfProficient,
                    default => ProficiencyType::Proficient
                }
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
            fn (ApiModifier $m) => $m->entityTypeId !== ApiProficiencyGroupEntityTypeId::Tool->value
        );
    }

    /**
     * @return array<int, CharacterProficiency>
     */
    public function getWeaponProficiences(): array
    {
        $weaponEntityIdList = [
            ApiProficiencyGroupEntityTypeId::WeaponGroup->value,
            ApiProficiencyGroupEntityTypeId::Weapon->value,
        ];

        $proficiencies = [];
        foreach ($this->modifiers as $m) {
            if (
                $m->entityTypeId === null
                || !\in_array($m->entityTypeId, $weaponEntityIdList, true)
                || !$this->isAvailableDuringMultiClass($m)
            ) {
                continue;
            }

            $proficiencies[$m->entityTypeId . '-' . $m->entityId] = new CharacterProficiency(
                ApiProficiencyGroupEntityTypeId::from($m->entityTypeId)->toProficiencyGroup(),
                !empty($m->restriction)
                    ? $m->friendlySubtypeName . ' (' . $m->restriction . ')'
                    : $m->friendlySubtypeName,
                match ($m->type) {
                    'proficiency' => ProficiencyType::Proficient,
                    'expertise' => ProficiencyType::Expertise,
                    'half-proficiency',
                    'half-proficiency-round-up' => ProficiencyType::HalfProficient,
                    default => ProficiencyType::Proficient
                }
            );
        }

        $filterProficiencies = [];
        if (isset($proficiencies[ApiProficiencyGroupEntityTypeId::WeaponGroup->value . '-' . ApiWeaponGroupEntityId::SimpleWeapon->value])) {
            $filterProficiencies = array_merge(
                $filterProficiencies,
                ApiSimpleWeaponEntityId::getValues(),
                ApiSimpleRangedWeaponEntityId::getValues()
            );
        }

        if (isset($proficiencies[ApiProficiencyGroupEntityTypeId::WeaponGroup->value . '-' . ApiWeaponGroupEntityId::MartialWeapon->value])) {
            $filterProficiencies = array_merge(
                $filterProficiencies,
                ApiMartialWeaponEntityId::getValues(),
                ApiMartialRangedWeaponEntityId::getValues()
            );
        }

        if (!empty($filterProficiencies)) {
            $filterProficiencies = array_map(
                fn ($entityId) => ApiProficiencyGroupEntityTypeId::Weapon->value . '-' . $entityId,
                $filterProficiencies
            );

            $proficiencies = array_filter(
                $proficiencies,
                fn ($p) => !in_array($p, $filterProficiencies, true),
                ARRAY_FILTER_USE_KEY
            );
        }

        \uasort($proficiencies, fn ($a, $b) => $a->name <=> $b->name);

        return \array_values($proficiencies);
    }

    private function getArmorTypeFromArmorTypeId(int $typeId): ?ArmorType
    {
        return match ($typeId) {
            1 => ArmorType::LightArmor,
            2 => ArmorType::MediumArmor,
            3 => ArmorType::HeavyArmor,
            4 => ArmorType::Shield,
            default => null
        };
    }

    private function isAvailableDuringMultiClass(ApiModifier $modifier): bool
    {
        $multiClasses = $this->apiCharacter->classes;

        if (count($multiClasses) <= 1) {
            return true;
        }

        /*
         * Create a list of feats that should not be available while multiclassing. Feats from the
         * first chosen class should still be available hence why we skip it.
         */
        array_shift($multiClasses);

        $levelOneProficiencies = array_map(
            fn ($f) => $f->id,
            array_filter(
                array_merge(...array_column(array_column($multiClasses, 'definition'), 'classFeatures')),
                fn ($f) => $f->requiredLevel === 1
                    && $f->name === 'Proficiencies'
            )
        );

        return $modifier->availableToMulticlass
            || !in_array($modifier->componentId, $levelOneProficiencies);
    }
}
