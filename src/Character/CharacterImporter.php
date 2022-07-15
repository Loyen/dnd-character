<?php

namespace loyen\DndbCharacterSheet\Character;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use loyen\DndbCharacterSheet\Character\Exception\CharacterInvalidImportException;
use loyen\DndbCharacterSheet\Character\Model\Character;
use loyen\DndbCharacterSheet\Character\Model\CharacterAbility;
use loyen\DndbCharacterSheet\Character\Model\CharacterAbilityTypes;
use loyen\DndbCharacterSheet\Character\Model\CharacterMovement;
use loyen\DndbCharacterSheet\Character\Model\CharacterMovementTypes;

class CharacterImporter
{
    public static function importFromApiById(int $characterId): Character
    {
        try {
            $client = new Client([
                'base_uri'  => 'https://character-service.dndbeyond.com/',
                'timeout'   => 2
            ]);

            $response = $client->request('GET', 'character/v5/character/' . $characterId);

            return self::createCharacterFromJson($response->getBody());
        } catch (GuzzleException $e) {
            \trigger_error('Could not get a response from DNDBeyond character API. Message: ' . $e->getMessage());
        }
    }

    public static function importFromJson(string $jsonString): Character
    {
        return self::createCharacterFromJson($jsonString);
    }

    private static function createCharacterFromJson(string $jsonString): Character
    {
        $jsonData = \json_decode($jsonString, true)['data'] ?? throw new CharacterInvalidImportException();

        return new Character(
            $jsonData['name'],
            self::extractAbilityScoresFromData($jsonData),
            self::extractProficiencyBonus($jsonData),
            self::extractMovementSpeeds($jsonData),
            self::extractLanguagesFromData($jsonData),
            self::extractToolProficienciesFromData($jsonData),
        );
    }

    public static function extractMovementSpeeds(array $data): array
    {
        $walkingSpeed = $data['race']['weightSpeeds']['normal']['walk'];
        $modifiers = $data['modifiers'];

        $flatModifiers = array_merge(...array_values($modifiers));

        $walkingModifiers = array_column(array_filter(
                $flatModifiers,
                fn ($m) => 1 === $m['modifierTypeId'] && 1685 === $m['modifierSubTypeId']
            ),
            'value'
        );

        $speedCollection = [
            new CharacterMovement(
                CharacterMovementTypes::from('walk'),
                $walkingSpeed,
                $walkingModifiers
            )
        ];

        $flyingModifiers = array_filter(
            $flatModifiers,
            fn ($m) => 9 === $m['modifierTypeId'] && 182 === $m['modifierSubTypeId']
        );

        if (!empty($flyingModifiers)) {
            $speedCollection[] = new CharacterMovement(
                CharacterMovementTypes::from('fly'),
                $walkingSpeed,
                $walkingModifiers
            );
        }

        return $speedCollection;
    }

    public static function extractProficiencyBonus(array $data): int
    {
        $level = min(20, array_sum(array_column($data['classes'], 'level')));

        return match (true) {
            $level <= 4 => 2,
            $level <= 8 => 3,
            $level <= 12 => 4,
            $level <= 16 => 5,
            $level <= 20 => 6
        };
    }

    public static function extractAbilityScoresFromData(array $data): array
    {
        $stats = $data['stats'];
        $modifiers = $data['modifiers'];

        $flatModifiers = array_merge(...array_values($modifiers));

        $statsEntityTypeId = 1472902489;
        $statsModifiers = array_filter(
            $flatModifiers,
            fn ($m) => $m['entityTypeId'] === $statsEntityTypeId
        );

        $modifiersList = [];
        foreach ($statsModifiers as $statModifier) {
            $entityId = $statModifier['entityId'];
            $modifiersList[$entityId][] = $statModifier['value'];
        }

        foreach ($data['bonusStats'] as $bonusStat) {
            if (!empty($bonusStat['value'])) {
                $entityId = $bonusStat['id'];
                $modifiersList[$entityId][] = $bonusStat['value'];
            }
        }

        $overrideList = [];
        foreach ($data['overrideStats'] as $overrideStat) {
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
            $characterAbilityType = CharacterAbilityTypes::from($statId);
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

    public static function extractLanguagesFromData(array $data): array
    {
        $modifiers = $data['modifiers'];

        $flatModifiers = array_merge(...array_values($modifiers));
        $languages = array_column(array_filter(
                $flatModifiers,
                fn ($m) => $m['type'] === 'language'
            ),
            'friendlySubtypeName'
        );

        sort($languages);

        return $languages;
    }

    public static function extractToolProficienciesFromData(array $data): array
    {
        $modifiers = $data['modifiers'];

        $flatModifiers = array_merge(...array_values($modifiers));
        $tools = array_column(array_filter(
                $flatModifiers,
                fn ($m) => $m['entityTypeId'] === 2103445194
            ),
            'friendlySubtypeName'
        );

        sort($tools);

        return $tools;
    }
}
