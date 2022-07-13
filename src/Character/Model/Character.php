<?php

namespace loyen\DndbCharacterLight\Character\Model;

use loyen\DndbCharacterLight\Character\Model\Exception\CharacterInvalidImport;

class Character implements \JsonSerializable
{
    private string $name;
    private array $stats;

    public function __construct()
    {
    }

    public static function importFromJson(string $jsonString): self
    {

        $jsonData = \json_decode($jsonString, true)['data'] ?? throw new CharacterInvalidImport();

        $self = new self();
        $self->name = $jsonData['name'];
        $self->stats = self::extractStatsFromJson($jsonData);

        return $self;
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

        $statsCollection = [];
        foreach ($stats as $stat) {
            $statId = $stat['id'];
            $statsCollection[] = new CharacterStat(
                CharacterStatTypes::from($statId),
                $stat['value'],
                $modifiersList[$statId] ?? []
            );
        }

        return $statsCollection;
    }

    public function jsonSerialize(): mixed
    {
        return \get_object_vars($this);
    }
}
