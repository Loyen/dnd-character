<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model;

class ApiCharacter
{
    public function __construct(
        public readonly string $name,
        /** @var array<int, ApiClass> */
        public readonly array $classes,
        public readonly ApiRace $race,
        /** @var array<int, ApiFeat> */
        public readonly array $feats,
        /** @var array<string|int, array<int, ApiModifier>> */
        public readonly array $modifiers,
        /** @var array<string|int, array<int, ApiOption>> */
        public readonly array $options,
        /** @var array<string|int, array<int, ApiChoice>> */
        public readonly array $choices,
        /**
         * Inventory related.
         */
        /** @var array<string, int> */
        public readonly array $currencies,
        /** @var array<int, ApiInventoryItem> */
        public readonly array $inventory,
        /**
         * Ability scores related.
         */
        /** @var array<int, ApiStat> */
        public readonly array $stats,
        /** @var array<int, ApiStat> */
        public readonly array $bonusStats,
        /** @var array<int, ApiStat> */
        public readonly array $overrideStats,
        /**
         * Hitpoints related.
         */
        public readonly int $baseHitPoints,
        public readonly ?int $bonusHitPoints,
        public readonly ?int $overrideHitPoints,
        public readonly ?int $removedHitPoints,
        public readonly ?int $temporaryHitPoints,
        /**
         * Custom stuff.
         */
        /** @var array<int, ApiCustomProficiency> */
        public readonly ?array $customProficiencies
    ) {}

    public static function fromApi(string $json): ?self
    {
        $data = \json_decode($json, true)['data'] ?? null;

        if ($data === null) {
            return null;
        }

        return new self(
            $data['name'],
            ApiClass::createCollectionFromApi($data['classes']),
            ApiRace::fromApi($data['race']),
            ApiFeat::createCollectionFromApi($data['feats']),
            ApiModifier::createCollectionFromApi($data['modifiers']),
            ApiOption::createCollectionPerCategoryFromApi($data['options']),
            ApiChoice::createCollectionPerCategoryFromApi($data['choices']),
            $data['currencies'],
            ApiInventoryItem::createCollectionFromApi($data['inventory']),
            ApiStat::createCollectionFromApi($data['stats']),
            ApiStat::createCollectionFromApi($data['bonusStats']),
            ApiStat::createCollectionFromApi($data['overrideStats']),
            $data['baseHitPoints'],
            $data['bonusHitPoints'],
            $data['overrideHitPoints'],
            $data['removedHitPoints'],
            $data['temporaryHitPoints'],
            ApiCustomProficiency::createCollectionFromApi($data['customProficiencies'])
        );
    }
}
