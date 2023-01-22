<?php

namespace loyen\DndbCharacterSheet\Model\DnDBeyond;

class ApiInventoryItemDefinition
{
    public function __construct(
        public readonly int $id,
        public readonly int $baseTypeId,
        public readonly int $entityTypeId,
        public readonly ?string $definitionKey,
        public readonly bool $canEquip,
        public readonly bool $magic,
        public readonly string $name,
        public readonly ?string $snippet,
        public readonly float $weight,
        public readonly int $weightMultiplier,
        public readonly ?string $capacity,
        public readonly float $capacityWeight,
        public readonly ?string $type,
        public readonly string $description,
        public readonly bool $canAttune,
        public readonly ?string $attunementDescription,
        public readonly string $rarity,
        public readonly bool $isHomebrew,
        public readonly ?string $version,
        public readonly ?int $sourceId,
        public readonly ?int $sourcePageNumber,
        public readonly bool $stackable,
        public readonly int $bundleSize,
        public readonly ?string $avatarUrl,
        public readonly ?string $largeAvatarUrl,
        public readonly string $filterType,
        public readonly ?float $cost,
        public readonly bool $isPack,
        /** @var array<int, string> */
        public readonly array $tags,
        /** @var array<int, string> */
        public readonly array $grantedModifiers,
        public readonly ?string $subType,
        public readonly bool $isConsumable,
        /** @var array<int, mixed> */
        public readonly array $weaponBehaviors,
        public readonly ?int $baseItemId,
        public readonly ?string $baseArmorName,
        public readonly ?int $strengthRequirement,
        public readonly ?int $armorClass,
        public readonly ?int $stealthCheck,
        public readonly ?ApiDice $damage,
        public readonly ?string $damageType,
        public readonly string|int|float|null $fixedDamage,
        /** @var array<int, ApiProperty>|null */
        public readonly ?array $properties,
        public readonly ?int $attackType,
        public readonly ?int $categoryId,
        public readonly ?int $range,
        public readonly ?int $longRange,
        public readonly bool $isMonkWeapon,
        public readonly ?int $levelInfusionGranted,
        /** @var array<int, ApiBookSource>|null */
        public readonly ?array $sources,
        public readonly ?int $armorTypeId,
        public readonly ?int $gearTypeId,
        public readonly ?int $groupedId,
        public readonly bool $canBeAddedToInventory,
        public readonly bool $isContainer,
        public readonly bool $isCustomItem
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['id'],
            $data['baseTypeId'],
            $data['entityTypeId'],
            $data['definitionKey'] ?? null,
            $data['canEquip'],
            $data['magic'],
            $data['name'],
            $data['snippet'],
            $data['weight'],
            $data['weightMultiplier'],
            $data['capacity'],
            $data['capacityWeight'],
            $data['type'],
            $data['description'],
            $data['canAttune'],
            $data['attunementDescription'],
            $data['rarity'],
            $data['isHomebrew'],
            $data['version'],
            $data['sourceId'],
            $data['sourcePageNumber'],
            $data['stackable'],
            $data['bundleSize'],
            $data['avatarUrl'],
            $data['largeAvatarUrl'],
            $data['filterType'],
            $data['cost'],
            $data['isPack'],
            $data['tags'],
            $data['grantedModifiers'],
            $data['subType'],
            $data['isConsumable'],
            $data['weaponBehaviors'],
            $data['baseItemId'],
            $data['baseArmorName'],
            $data['strengthRequirement'],
            $data['armorClass'],
            $data['stealthCheck'],
            $data['damage'] !== null ? ApiDice::fromApi($data['damage']) : null,
            $data['damageType'],
            $data['fixedDamage'],
            $data['properties'] !== null ? ApiProperty::createCollectionFromApi($data['properties']) : null,
            $data['attackType'],
            $data['categoryId'],
            $data['range'],
            $data['longRange'],
            $data['isMonkWeapon'],
            $data['levelInfusionGranted'],
            ApiBookSource::createCollectionFromApi($data['sources']),
            $data['armorTypeId'],
            $data['gearTypeId'],
            $data['groupedId'],
            $data['canBeAddedToInventory'],
            $data['isContainer'],
            $data['isCustomItem']
        );
    }
}
