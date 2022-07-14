<?php

namespace loyen\DndbCharacterSheet\Character;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use loyen\DndbCharacterSheet\Character\Exception\CharacterInvalidImportException;
use loyen\DndbCharacterSheet\Character\Model\Character;
use loyen\DndbCharacterSheet\Character\Model\CharacterAbility;
use loyen\DndbCharacterSheet\Character\Model\CharacterAbilityTypes;

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
        } catch (ClientException $e) {
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
            self::extractStatsFromData($jsonData)
        );
    }

    public static function extractStatsFromData(array $data): array
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

        $statsCollection = [];
        foreach ($stats as $stat) {
            $statId = $stat['id'];
            $statsCollection[] = new CharacterAbility(
                CharacterAbilityTypes::from($statId),
                $stat['value'],
                $modifiersList[$statId] ?? [],
                $overrideList[$statId] ?? null
            );
        }

        return $statsCollection;
    }
}
