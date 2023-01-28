<?php

namespace loyen\DndbCharacterSheet\Model\DnDBeyond;

class ApiInventoryItem
{
    public function __construct(
        public readonly int $id,
        public readonly int $entityTypeId,
        public readonly ApiInventoryItemDefinition $definition,
        public readonly int $definitionId,
        public readonly int $definitionTypeId,
        public readonly ?bool $displayAsAttack,
        public readonly int $quantity,
        public readonly bool $isAttuned,
        public readonly bool $equipped,
        public readonly ?int $equippedEntityTypeId,
        public readonly ?int $equippedEntityId,
        public readonly int $chargesUsed,
        /** @var array<string, mixed> */
        public readonly ?array $limitedUse,
        public readonly int $containerEntityId,
        public readonly int $containerEntityTypeId,
        public readonly string $containerDefinitionKey,
        public readonly ?int $currency
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['id'],
            $data['entityTypeId'],
            ApiInventoryItemDefinition::fromApi($data['definition']),
            $data['definitionId'],
            $data['definitionTypeId'],
            $data['displayAsAttack'],
            $data['quantity'],
            $data['isAttuned'],
            $data['equipped'],
            $data['equippedEntityTypeId'],
            $data['equippedEntityId'],
            $data['chargesUsed'],
            $data['limitedUse'],
            $data['containerEntityId'],
            $data['containerEntityTypeId'],
            $data['containerDefinitionKey'],
            $data['currency'],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $data
     * @return array<int, self>
     */
    public static function createCollectionFromApi(array $data): array
    {
        $itemCollection = [];

        foreach ($data as $stat) {
            $itemCollection[] = self::fromApi($stat);
        }

        return $itemCollection;
    }
}
