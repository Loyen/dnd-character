<?php

namespace loyen\DndbCharacterSheet\Character;

use loyen\DndbCharacterSheet\Character\Exception\CharacterInvalidImportException;
use loyen\DndbCharacterSheet\Character\Model\Character;
use loyen\DndbCharacterSheet\Character\Model\CharacterStat;
use loyen\DndbCharacterSheet\Character\Model\CharacterStatTypes;

class CharacterImporter
{
    public static function importFromJson(string $jsonString): Character
    {

        $jsonData = \json_decode($jsonString, true)['data'] ?? throw new CharacterInvalidImportException();

        $character = new Character(
            $jsonData['name'],
            self::extractStatsFromJson($jsonData)
        );

        return $character;
    }

    public static function extractStatsFromJson(array $data): array
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
            $statsCollection[] = new CharacterStat(
                CharacterStatTypes::from($statId),
                $stat['value'],
                $modifiersList[$statId] ?? [],
                $overrideList[$statId] ?? null
            );
        }

        return $statsCollection;
    }
}
