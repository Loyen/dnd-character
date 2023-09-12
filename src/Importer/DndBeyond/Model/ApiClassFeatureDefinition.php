<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model;

class ApiClassFeatureDefinition
{
    public function __construct(
        public readonly int $id,
        public readonly string $definitionKey,
        public readonly int $entityTypeId,
        public readonly int $displayOrder,
        public readonly string $name,
        public readonly string $description,
        public readonly ?string $snippet,
        /** @var array<string, int>|null */
        public readonly mixed $activation,
        public readonly string $multiClassDescription,
        public readonly int $requiredLevel,
        public readonly bool $isSubClassFeature,
        /** @var array<string, mixed> */
        public readonly ?array $limitedUse,
        public readonly bool $hideInBuilder,
        public readonly bool $hideInSheet,
        public readonly int $sourceId,
        public readonly int $sourcePageNumber,
        /** @var array<int, array<string, mixed>> */
        public readonly array $creatureRules,
        /** @var array<int, ApiLevelScale> */
        public readonly array $levelScales,
        /** @var array<int, mixed> */
        public readonly array $infusionRules,
        /** @var array<int, mixed> */
        public readonly array $spellListIds,
        public readonly int $classId,
        public readonly int $featureType,
        /** @var array<int, ApiBookSource> */
        public readonly array $sources,
        /** @var array<int, mixed> */
        public readonly array $affectedFeatureDefinitionKeys,
        public readonly string $entityType,
        public readonly int $entityId
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['id'],
            $data['definitionKey'],
            $data['entityTypeId'],
            $data['displayOrder'],
            $data['name'],
            $data['description'],
            $data['snippet'],
            $data['activation'],
            $data['multiClassDescription'],
            $data['requiredLevel'],
            $data['isSubClassFeature'],
            $data['limitedUse'],
            $data['hideInBuilder'],
            $data['hideInSheet'],
            $data['sourceId'],
            $data['sourcePageNumber'],
            $data['creatureRules'],
            ApiLevelScale::createCollectionFromApi($data['levelScales']),
            $data['infusionRules'],
            $data['spellListIds'],
            $data['classId'],
            $data['featureType'],
            ApiBookSource::createCollectionFromApi($data['sources']),
            $data['affectedFeatureDefinitionKeys'],
            $data['entityType'],
            $data['entityID']
        );
    }
}
