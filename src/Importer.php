<?php

namespace loyen\DndbCharacterSheet;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use loyen\DndbCharacterSheet\Exception\CharacterAPIException;
use loyen\DndbCharacterSheet\Exception\CharacterException;
use loyen\DndbCharacterSheet\Exception\CharacterFileReadException;
use loyen\DndbCharacterSheet\Exception\CharacterInvalidImportException;
use loyen\DndbCharacterSheet\Model\AbilityType;
use loyen\DndbCharacterSheet\Model\Character;
use loyen\DndbCharacterSheet\Model\CharacterAbility;
use loyen\DndbCharacterSheet\Model\CharacterArmorClass;
use loyen\DndbCharacterSheet\Model\CharacterClass;
use loyen\DndbCharacterSheet\Model\CharacterHealth;
use loyen\DndbCharacterSheet\Model\CharacterMovement;
use loyen\DndbCharacterSheet\Model\CharacterProficiency;
use loyen\DndbCharacterSheet\Model\CurrencyType;
use loyen\DndbCharacterSheet\Model\Item;
use loyen\DndbCharacterSheet\Model\MovementType;
use loyen\DndbCharacterSheet\Model\ProficiencyType;

class Importer
{
    /**
     * @var array<string, mixed> $data
     */
    private array $data;
    /**
     * @var array<int, array<string, mixed>> $modifiers
     */
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
        $this->data = \json_decode($jsonString, true)['data'] ?? throw new CharacterInvalidImportException();

        $modifiers = $this->data['modifiers'];

        unset($modifiers['item']);
        $this->modifiers = \array_merge(...\array_values($modifiers));
    }

    public function createCharacter(): Character
    {
        $this->character = new Character();

        $this->character->setName($this->data['name']);
        $this->character->setInventory($this->getInventory());
        $this->character->setAbilityScores($this->getAbilityScores());
        $this->character->setArmorClass($this->getArmorClass());
        $this->character->setClasses($this->getClasses());
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
        $stats = $this->data['stats'];

        $modifierList = [];
        $savingThrowsProficiencies = [];
        $acceptedComponentTypeIds = [
            1960452172, // origin,
            306912077, // racial
            12168134, // ability-score
            1088085227, // squat nimbleness
        ];
        foreach ($this->modifiers as $m) {
            $mId = $m['entityId'];

            if (
                $m['value'] !== null
                && $m['entityTypeId'] === 1472902489
                && \in_array(
                    $m['componentTypeId'],
                    $acceptedComponentTypeIds,
                    true
                )
            ) {
                $modifierList[$mId][] = $m['value'];
            } elseif (
                $m['type'] === 'proficiency'
                && \str_ends_with($m['subType'], '-saving-throws')
            ) {
                $savingThrowCode = $m['subType'];
                $savingThrowsProficiencies[$savingThrowCode] = $m['type'];
            }
        }

        foreach ($this->data['bonusStats'] as $bonusStat) {
            if (!empty($bonusStat['value'])) {
                $entityId = $bonusStat['id'];
                $modifierList[$entityId][] = $bonusStat['value'];
            }
        }

        $overrideList = [];
        foreach ($this->data['overrideStats'] as $overrideStat) {
            if (!empty($overrideStat['value'])) {
                $entityId = $overrideStat['id'];
                $overrideList[$entityId] = $overrideStat['value'];
            }
        }

        foreach ($this->getItemModifiers() as $itemModifier) {
            $entityId = $itemModifier['entityId'];
            if ($itemModifier['modifierTypeId'] === 9) {
                $overrideList[$entityId] = $itemModifier['value'];
            } else {
                $modifierList[$entityId][] = $itemModifier['value'];
            }
        }

        $statsCollection = [];
        foreach ($stats as $stat) {
            $statId = $stat['id'];
            $characterAbilityType = AbilityType::from($statId);
            $savingThrowCode = \strtolower($characterAbilityType->name()) . '-saving-throws';

            $ability = new CharacterAbility($characterAbilityType);
            $ability->setValue($stat['value']);
            $ability->setSavingThrowProficient(isset($savingThrowsProficiencies[$savingThrowCode]));

            if (isset($modifierList[$statId])) {
                $ability->setModifiers($modifierList[$statId]);
            }

            if (isset($overrideList[$statId])) {
                $ability->setOverrideValue($overrideList[$statId]);
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
            fn (array $m) => $m['entityTypeId'] !== ProficiencyType::ABILITY->value
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

            if (\in_array($item->getArmorTypeId(), [ 1, 2, 3 ], true)) {
                $armorClass->setArmor($item);
            } else if ($item->getArmorClass() !== null) {
                $armorBonuses[$item->getId()] = $item->getArmorClass();
            }

            foreach ($item->getModifierIds() as $modifierId) {
                if (!isset($itemModifiers[$modifierId])) {
                    continue;
                }

                $m = $itemModifiers[$modifierId];

                if (
                    $m['type'] === 'bonus'
                    && (
                        $m['subType'] === 'armor-class'
                        || $m['subType'] === 'armored-armor-class'
                    )
                    && $m['isGranted'] === true
                ) {
                    $armorBonuses[] = $itemModifiers[$modifierId]['value'];
                }
            }
        }

        $isWearingArmor = !empty($armorBonuses);

        foreach ($this->modifiers as $modifierId => $m) {
            $isArmored = $m['type'] === 'bonus'
                && \in_array(
                    $m['subType'],
                    [
                        'armored-armor-class',
                        'armor-class'
                    ],
                    true
                )
                && $m['modifierTypeId'] === 1
                && $m['modifierSubTypeId'] !== 1;

            $isUnarmored = $m['type'] === 'set'
                && $m['subType'] === 'unarmored-armor-class'
                && $m['modifierTypeId'] === 9
                && $m['modifierSubTypeId'] === 1006;

            if ($isArmored || $isUnarmored) {
                if (!$isWearingArmor) {
                    /**
                     * Natural Armor = CON instead of DEX.
                     * Unarmored Defense = DEX + WIS or DEX + CON.
                     */
                    if ($m['componentId'] === 571068) {
                        $armorClass->addAbilityScore(
                            $this->character->getAbilityScores()[AbilityType::CON->name]
                        );
                    } elseif ($m['componentId'] === 226) {
                        $armorClass->addAbilityScore(
                            $this->character->getAbilityScores()[AbilityType::DEX->name]
                        );
                        $armorClass->addAbilityScore(
                            $this->character->getAbilityScores()[AbilityType::WIS->name]
                        );
                        $unarmoredDefense = true;
                    } elseif ($m['componentId'] === 52) {
                        $armorClass->addAbilityScore(
                            $this->character->getAbilityScores()[AbilityType::DEX->name]
                        );
                        $armorClass->addAbilityScore(
                            $this->character->getAbilityScores()[AbilityType::CON->name]
                        );
                        $unarmoredDefense = true;
                    }
                } elseif (
                    $m['value'] !== null
                    && $m['subType'] !== 'unarmored-armor-class'
                ) {
                    $armorBonuses[] = $m['value'];
                }
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
            fn (array $m) => $m['entityTypeId'] !== ProficiencyType::ARMOR->value
        );
    }

    /**
     * @return array<int, CharacterClass>
     */
    public function getClasses(): array
    {
        $classes = $this->data['classes'];
        $classOptions = \array_column($this->data['options']['class'], null, 'componentId');

        // Do not include any of these in the features list
        $skippedFeatures = [
            'Ability Score Improvement',
            'Hit Points',
            'Proficiencies',
            'Fast Movement'
        ];

        $classList = [];
        foreach ($classes as $class) {
            $characterClass = new CharacterClass($class['definition']['name']);
            $characterClass->setLevel($class['level']);

            $classFeatures = $class['definition']['classFeatures'];

            if (isset($class['subclassDefinition'])) {
                $characterClass->setSubName($class['subclassDefinition']['name']);

                $classFeatures = \array_merge($classFeatures, $class['subclassDefinition']['classFeatures']);
            }

            foreach ($classFeatures as $feature) {
                $featureName = isset($classOptions[$feature['id']]['definition']['name'])
                    ? $feature['name'] . ' - ' . $classOptions[$feature['id']]['definition']['name']
                    : $feature['name'];

                if (
                    \in_array($featureName, $characterClass->getFeatures(), true)
                    || $feature['requiredLevel'] > $class['level']
                    || \in_array($feature['name'], $skippedFeatures, true)
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
        $currencies = $this->data['currencies'];

        $currencyList = [];
        foreach (CurrencyType::cases() as $currency) {
            $currencyList[$currency->value] = $currencies[$currency->value];
        }

        return $currencyList;
    }

    public function getHealth(): CharacterHealth
    {
        $baseHitPoints = (int) $this->data['baseHitPoints'];

        $healthModifiers = [];
        if (isset($this->data['bonusHitPoints'])) {
            $healthModifiers[] = $this->data['bonusHitPoints'];
        }
        if (isset($this->data['removedHitPoints'])) {
            $healthModifiers[] = -$this->data['removedHitPoints'];
        }

        $constituionScore = $this->character->getAbilityScores()[AbilityType::CON->name];
        $baseHitPoints += (int) \floor($this->character->getLevel() * $constituionScore->getCalculatedModifier());

        return new CharacterHealth(
            $baseHitPoints,
            $healthModifiers,
            $this->data['temporaryHitPoints'] ?? 0,
            $this->data['overrideHitPoints'] ?? null,
        );
    }

    /**
     * @return array<int, Item>
     */
    public function getInventory(): array
    {
        $inventory = $this->data['inventory'];

        $itemList = [];
        foreach ($inventory as $iItem) {
            $iItemDefinition = $iItem['definition'];
            $item = new Item(
                $iItemDefinition['name'],
                $iItemDefinition['filterType']
            );
            $item->setId($iItemDefinition['id']);
            $item->setTypeId($iItemDefinition['entityTypeId']);

            $subType = $iItemDefinition['subType'] ?? $iItemDefinition['type'];

            if ($iItemDefinition['filterType'] !== $subType) {
                $item->setSubType($subType);
            }

            $item->setQuantity($iItem['quantity']);
            $item->setCanAttune($iItemDefinition['canAttune']);
            $item->setIsAttuned($iItem['isAttuned']);
            $item->setIsConsumable($iItemDefinition['isConsumable']);
            $item->setIsEquipped($iItemDefinition['canEquip'] && $iItem['equipped']);
            $item->setIsMagical($iItemDefinition['magic']);

            if (isset($iItemDefinition['armorClass'])) {
                $item->setArmorClass($iItemDefinition['armorClass']);
            }

            if (isset($iItemDefinition['armorTypeId'])) {
                $item->setArmorTypeId($iItemDefinition['armorTypeId']);
            }

            if (isset($iItemDefinition['damageType'])) {
                $item->setDamageType($iItemDefinition['damageType']);
            }

            if (isset($iItemDefinition['damage']['diceString'])) {
                $item->setDamage($iItemDefinition['damage']['diceString']);
            }

            if (isset($iItemDefinition['range'])) {
                $item->setRange($iItemDefinition['range']);
            }

            if (isset($iItemDefinition['longRange'])) {
                $item->setLongRange($iItemDefinition['longRange']);
            }

            if (isset($iItemDefinition['properties'])) {
                foreach ($iItemDefinition['properties'] as $p) {
                    $item->addProperty($p['name']);
                }
            }

            if (isset($iItemDefinition['grantedModifiers'])) {
                $item->setModifierIds(\array_values(\array_unique(\array_column(
                    $iItemDefinition['grantedModifiers'],
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
            fn (array $m) => $m['entityTypeId'] !== 906033267
        );
    }

    public function getLevel(): int
    {
        return (int) \min(20, \array_sum(\array_column($this->data['classes'], 'level')));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getItemModifiers(): array
    {
        $itemModifiers = \array_column($this->data['modifiers']['item'], null, 'id');

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
        $walkingSpeed = $this->data['race']['weightSpeeds']['normal']['walk'];

        $walkingSpeedModifierSubTypes = [
            1685, // unarmored-movement
            1697  // speed-walking
        ];

        $walkingModifiers = \array_column(
            \array_filter(
                $this->modifiers,
                fn (array $m) => 1 === $m['modifierTypeId']
                    && \in_array($m['modifierSubTypeId'], $walkingSpeedModifierSubTypes, true)
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
            fn (array $m) => 9 === $m['modifierTypeId'] && 182 === $m['modifierSubTypeId']
        );

        if (!empty($flyingModifiers)) {
            $flyingSpeed = \array_column($flyingModifiers, 'value');
            $flyingSpeed = !empty($flyingSpeed) ? \max($flyingSpeed) : false;
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
            $mId = $m['entityId'];
            if (
                isset($proficiencies[$mId])
                || $function($m)
            ) {
                continue;
            }

            $proficiencies[$mId] = new CharacterProficiency(
                ProficiencyType::from($m['entityTypeId']),
                $m['friendlySubtypeName'],
                $m['type'] === 'expertise'
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
            fn (array $m) => $m['entityTypeId'] !== ProficiencyType::TOOL->value
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
            fn (array $m) => !\in_array($m['entityTypeId'], $weaponEntityIdList, true)
        );
    }
}
