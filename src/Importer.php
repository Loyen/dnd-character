<?php

namespace loyen\DndbCharacterSheet;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use loyen\DndbCharacterSheet\Exception\CharacterAPIException;
use loyen\DndbCharacterSheet\Exception\CharacterInvalidImportException;
use loyen\DndbCharacterSheet\Model\AbilityType;
use loyen\DndbCharacterSheet\Model\Character;
use loyen\DndbCharacterSheet\Model\CharacterAbility;
use loyen\DndbCharacterSheet\Model\CharacterHealth;
use loyen\DndbCharacterSheet\Model\CharacterMovement;
use loyen\DndbCharacterSheet\Model\CurrencyType;
use loyen\DndbCharacterSheet\Model\MovementType;

class Importer
{
    private array $data;
    private array $modifiers;

    public static function importFromApiById(int $characterId): Character
    {
        try {
            $client = new Client([
                'base_uri'  => 'https://character-service.dndbeyond.com/',
                'timeout'   => 2
            ]);

            $response = $client->request('GET', 'character/v5/character/' . $characterId);

            return (new self($response->getBody()))->createCharacter();
        } catch (GuzzleException $e) {
            throw new CharacterAPIException('Could not get a response from DNDBeyond character API. Message: ' . $e->getMessage());
        }
    }

    public static function importFromJson(string $jsonString): Character
    {
        return (new self($jsonString))->createCharacter();
    }

    public function __construct(string $jsonString)
    {
        $this->data = \json_decode($jsonString, true)['data'] ?? throw new CharacterInvalidImportException();
    }

    public function createCharacter(): Character
    {
        $character = new Character();

        $character->setName($this->data['name']);
        $character->setAbilityScores($this->getAbilityScores());
        $character->setClasses($this->getClasses());
        $character->setCurrencies($this->getCurrencies());
        $character->setHealth($this->getHealth());
        $character->setProficiencyBonus($this->getProficiencyBonus());
        $character->setMovementSpeeds($this->getMovementSpeeds());
        $character->setProficiencies([
            'armor'     => $this->getArmorProficiencies(),
            'languages' => $this->getLanguages(),
            'tools'     => $this->getToolProficiencies(),
            'weapons'   => $this->getWeaponProficiences(),
        ]);

        return $character;
    }

    public function getAbilityScores(): array
    {
        $stats = $this->data['stats'];
        $modifiers = $this->getModifiers();
        $statsModifiers = array_filter(
            $modifiers,
            fn ($m) => 1472902489 === $m['entityTypeId'] &&
                       null !== $m['value']
        );

        $modifiersList = [];
        foreach ($statsModifiers as $statModifier) {
            $entityId = $statModifier['entityId'];
            $modifiersList[$entityId][] = $statModifier['value'];
        }

        foreach ($this->data['bonusStats'] as $bonusStat) {
            if (!empty($bonusStat['value'])) {
                $entityId = $bonusStat['id'];
                $modifiersList[$entityId][] = $bonusStat['value'];
            }
        }

        $overrideList = [];
        foreach ($this->data['overrideStats'] as $overrideStat) {
            if (!empty($overrideStat['value'])) {
                $entityId = $overrideStat['id'];
                $overrideList[$entityId] = $overrideStat['value'];
            }
        }

        $savingThrowsProficiencies = array_column(array_filter(
            $modifiers,
            fn ($m) => $m['type'] === 'proficiency' &&
                       str_ends_with($m['subType'], '-saving-throws')
            ),
            'type',
            'subType'
        );

        $statsCollection = [];
        foreach ($stats as $stat) {
            $statId = $stat['id'];
            $characterAbilityType = AbilityType::from($statId);
            $savingThrowCode = strtolower($characterAbilityType->name()) . '-saving-throws';

            $statsCollection[] = new CharacterAbility(
                $characterAbilityType,
                $stat['value'],
                $modifiersList[$statId] ?? [],
                $overrideList[$statId] ?? null,
                isset($savingThrowsProficiencies[$savingThrowCode])
            );
        }

        return $statsCollection;
    }

    public function getArmorProficiencies(): array
    {
        $modifiers = $this->getModifiers();
        $armors = array_values(array_unique(array_column(array_filter(
                $modifiers,
                fn ($m) => $m['entityTypeId'] === 174869515
            ),
            'friendlySubtypeName'
        )));

        return $armors;
    }

    public function getClasses(): array
    {
        $classes = $this->data['classes'];
        $classOptions = array_column($this->data['options']['class'], null, 'componentId');

        // Do not include any of these in the features list
        $skippedFeatures = [
            'Ability Score Improvement',
            'Hit Points',
            'Proficiencies',
            'Fast Movement'
        ];

        $classList = [];
        foreach ($classes as $classPosition => $class) {
            $level = $class['level'];
            $name = $class['definition']['name'];

            $classList[$classPosition] = [
                'level' => $level,
                'name' => $name
            ];

            $classFeatures = $class['definition']['classFeatures'];

            if (isset($class['subclassDefinition'])) {
                $classList[$classPosition]['subName'] = $class['subclassDefinition']['name'];

                $classFeatures = array_merge($classFeatures, $class['subclassDefinition']['classFeatures']);
            }

            $unlockedClassFeatures = \array_filter(
                $classFeatures,
                fn ($f) => $f['requiredLevel'] <= $level &&
                           !in_array($f['name'], $skippedFeatures)
            );

            foreach ($unlockedClassFeatures as &$unlockedFeature) {
                if (isset($classOptions[$unlockedFeature['id']]['definition']['name'])) {
                    $unlockedFeature['name'] = sprintf(
                        '%s - %s',
                        $unlockedFeature['name'],
                        $classOptions[$unlockedFeature['id']]['definition']['name']
                    );
                }
            }

            usort($unlockedClassFeatures, fn($a, $b) => $a['name'] <=> $b['name']);

            $classList[$classPosition]['features'] = array_values(array_unique(array_column($unlockedClassFeatures, 'name')));
        }

        return $classList;
    }

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
        $baseHitPoints = $this->data['baseHitPoints'];

        $healthModifiers = [];
        if (isset($this->data['bonusHitPoints'])) {
            $healthModifiers[] = $this->data['bonusHitPoints'];
        }
        if (isset($this->data['removedHitPoints'])) {
            $healthModifiers[] = -$this->data['removedHitPoints'];
        }

        $level = $this->getLevel();
        $abilityScores = array_filter($this->getAbilityScores(), fn($a) => $a->type == AbilityType::CON);
        $constituionScore = array_shift($abilityScores);

        $baseHitPoints += $level * $constituionScore->getCalculatedModifier();

        return new CharacterHealth(
            $baseHitPoints,
            $healthModifiers,
            $this->data['temporaryHitPoints'] ?? 0,
            $this->data['overrideHitPoints'] ?? null,
        );
    }

    public function getLanguages(): array
    {
        $modifiers = $this->getModifiers();
        $languages = array_values(array_unique(array_column(array_filter(
                $modifiers,
                fn ($m) => $m['type'] === 'language'
            ),
            'friendlySubtypeName'
        )));

        sort($languages);

        return $languages;
    }

    public function getLevel(): int
    {
        return min(20, array_sum(array_column($this->data['classes'], 'level')));
    }

    public function getModifiers(): array
    {
        $this->modifiers ??= array_merge(...array_values($this->data['modifiers']));

        return $this->modifiers;
    }

    public function getMovementSpeeds(): array
    {
        $walkingSpeed = $this->data['race']['weightSpeeds']['normal']['walk'];
        $modifiers = $this->getModifiers();

        $walkingSpeedModifierSubTypes = [
            1685, // unarmored-movement
            1697  // speed-walking
        ];

        $walkingModifiers = array_column(array_filter(
                $modifiers,
                fn ($m) => 1 === $m['modifierTypeId'] &&
                                 in_array($m['modifierSubTypeId'], $walkingSpeedModifierSubTypes, true)
            ),
            'value'
        );

        $speedCollection = [
            new CharacterMovement(
                MovementType::from('walk'),
                $walkingSpeed,
                $walkingModifiers
            )
        ];

        $flyingModifiers = array_filter(
            $modifiers,
            fn ($m) => 9 === $m['modifierTypeId'] && 182 === $m['modifierSubTypeId']
        );

        if (!empty($flyingModifiers)) {
            $flyingSpeed = \max(array_column($flyingModifiers, 'value'));
            $speedCollection[] = new CharacterMovement(
                MovementType::from('fly'),
                $flyingSpeed ?: $walkingSpeed,
                $flyingSpeed ? [ 0 ] : $walkingModifiers
            );
        }

        return $speedCollection;
    }

    public function getProficiencyBonus(): int
    {
        $level = $this->getLevel();

        return match (true) {
            $level <= 4 => 2,
            $level <= 8 => 3,
            $level <= 12 => 4,
            $level <= 16 => 5,
            $level <= 20 => 6
        };
    }

    public function getToolProficiencies(): array
    {
        $modifiers = $this->getModifiers();
        $tools = array_values(array_unique(array_column(array_filter(
                $modifiers,
                fn ($m) => $m['entityTypeId'] === 2103445194
            ),
            'friendlySubtypeName'
        )));

        sort($tools);

        return $tools;
    }

    public function getWeaponProficiences(): array
    {
        $modifiers = $this->getModifiers();
        $weaponEntityIdList = [
            660121713, // Type
            1782728300, // Weapon-specific
        ];

        $weapons = array_values(array_unique(array_column(array_filter(
                $modifiers,
                fn ($m) => in_array($m['entityTypeId'], $weaponEntityIdList)
            ),
            'friendlySubtypeName'
        )));

        sort($weapons);

        return $weapons;
    }
}
