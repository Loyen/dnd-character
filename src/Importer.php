<?php

namespace loyen\DndbCharacterSheet;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use loyen\DndbCharacterSheet\Exception\CharacterAPIException;
use loyen\DndbCharacterSheet\Exception\CharacterInvalidImportException;
use loyen\DndbCharacterSheet\Model\AbilityType;
use loyen\DndbCharacterSheet\Model\Character;
use loyen\DndbCharacterSheet\Model\CharacterAbility;
use loyen\DndbCharacterSheet\Model\CharacterMovement;
use loyen\DndbCharacterSheet\Model\CurrencyType;
use loyen\DndbCharacterSheet\Model\MovementType;

class Importer
{
    private array $data;

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
        $character->setAbilityScores($this->extractAbilityScoresFromData());
        $character->setClasses($this->extractClassesFromData());
        $character->setCurrencies($this->extractCurrenciesFromData());
        $character->setProficiencyBonus($this->extractProficiencyBonusFromData());
        $character->setMovementSpeeds($this->extractMovementSpeedsFromData());
        $character->setProficiencies([
            'armor'     => $this->extractArmorProficienciesFromData(),
            'languages' => $this->extractLanguagesFromData(),
            'tools'     => $this->extractToolProficienciesFromData(),
            'weapons'   => $this->extractWeaponProficienciesFromData(),
        ]);

        return $character;
    }

    public function extractCurrenciesFromData(): array
    {
        $currencies = $this->data['currencies'];

        $currencyList = [];
        foreach (CurrencyType::cases() as $currency) {
            $currencyList[$currency->value] = $currencies[$currency->value];
        }

        return $currencyList;
    }

    public function extractMovementSpeedsFromData(): array
    {
        $walkingSpeed = $this->data['race']['weightSpeeds']['normal']['walk'];
        $modifiers = $this->data['modifiers'];

        $flatModifiers = array_merge(...array_values($modifiers));

        $walkingSpeedModifierSubTypes = [
            1685, // unarmored-movement
            1697  // speed-walking
        ];

        $walkingModifiers = array_column(array_filter(
                $flatModifiers,
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
            $flatModifiers,
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

    public function extractProficiencyBonusFromData(): int
    {
        $level = min(20, array_sum(array_column($this->data['classes'], 'level')));

        return match (true) {
            $level <= 4 => 2,
            $level <= 8 => 3,
            $level <= 12 => 4,
            $level <= 16 => 5,
            $level <= 20 => 6
        };
    }

    public function extractAbilityScoresFromData(): array
    {
        $stats = $this->data['stats'];
        $modifiers = $this->data['modifiers'];

        $flatModifiers = array_merge(...array_values($modifiers));

        $statsModifiers = array_filter(
            $flatModifiers,
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
            $flatModifiers,
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

    public function extractLanguagesFromData(): array
    {
        $modifiers = $this->data['modifiers'];

        $flatModifiers = array_merge(...array_values($modifiers));
        $languages = array_values(array_unique(array_column(array_filter(
                $flatModifiers,
                fn ($m) => $m['type'] === 'language'
            ),
            'friendlySubtypeName'
        )));

        sort($languages);

        return $languages;
    }

    public function extractToolProficienciesFromData(): array
    {
        $modifiers = $this->data['modifiers'];

        $flatModifiers = array_merge(...array_values($modifiers));
        $tools = array_values(array_unique(array_column(array_filter(
                $flatModifiers,
                fn ($m) => $m['entityTypeId'] === 2103445194
            ),
            'friendlySubtypeName'
        )));

        sort($tools);

        return $tools;
    }

    public function extractArmorProficienciesFromData(): array
    {
        $modifiers = $this->data['modifiers'];

        $flatModifiers = array_merge(...array_values($modifiers));

        $armors = array_values(array_unique(array_column(array_filter(
                $flatModifiers,
                fn ($m) => $m['entityTypeId'] === 174869515
            ),
            'friendlySubtypeName'
        )));

        return $armors;
    }

    public function extractWeaponProficienciesFromData(): array
    {
        $modifiers = $this->data['modifiers'];

        $flatModifiers = array_merge(...array_values($modifiers));
        $weaponEntityIdList = [
            660121713, // Type
            1782728300, // Weapon-specific
        ];

        $weapons = array_values(array_unique(array_column(array_filter(
                $flatModifiers,
                fn ($m) => in_array($m['entityTypeId'], $weaponEntityIdList)
            ),
            'friendlySubtypeName'
        )));

        sort($weapons);

        return $weapons;
    }

    public function extractClassesFromData(): array
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
}
