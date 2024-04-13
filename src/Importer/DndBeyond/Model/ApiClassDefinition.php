<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model;

class ApiClassDefinition
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $definitionKey,
        public readonly string $name,
        public readonly string $description,
        public readonly ?string $equipmentDescription,
        public readonly ?int $parentClassId,
        public readonly ?string $avatarUrl,
        public readonly ?string $largeAvatarUrl,
        public readonly ?string $portraitAvatarUrl,
        public readonly ?string $moreDetailsUrl,
        public readonly ?int $spellCastingAbilityId,
        /** @var array<int, ApiBookSource> */
        public readonly array $sources,
        /** @var array<int, ApiClassDefinitionFeature> */
        public readonly array $classFeatures,
        public readonly ?int $hitDice,
        public readonly ?ApiDice $wealthDice,
        public readonly bool $canCastSpells,
        public readonly mixed $knowsAllSpells,
        public readonly mixed $spellPrepareType,
        public readonly mixed $spellContainerName,
        public readonly ?int $sourcePageNumber,
        public readonly ?self $subclassDefinition,
        public readonly bool $isHomebrew,
        /** @var array<int, int>|null */
        public readonly ?array $primaryAbilities,
        /** @var array<string, mixed>|null */
        public readonly ?array $spellRules,
        /** @var array<int, object>|null */
        public readonly ?array $prerequisites,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['id'],
            $data['definitionKey'] ?? null,
            $data['name'],
            $data['description'],
            $data['equipmentDescription'],
            $data['parentClassId'],
            $data['avatarUrl'],
            $data['largeAvatarUrl'],
            $data['portraitAvatarUrl'],
            $data['moreDetailsUrl'],
            $data['spellCastingAbilityId'],
            ApiBookSource::createCollectionFromApi($data['sources']),
            ApiClassDefinitionFeature::createCollectionFromApi($data['classFeatures']),
            $data['hitDice'],
            $data['wealthDice'] !== null
                ? ApiDice::fromApi($data['wealthDice'])
                : null,
            $data['canCastSpells'],
            $data['knowsAllSpells'],
            $data['spellPrepareType'],
            $data['spellContainerName'],
            $data['sourcePageNumber'],
            $data['subclassDefinition'] !== null
                ? self::fromApi($data['subclassDefinition'])
                : null,
            $data['isHomebrew'],
            $data['primaryAbilities'],
            $data['spellRules'],
            $data['prerequisites'],
        );
    }
}
