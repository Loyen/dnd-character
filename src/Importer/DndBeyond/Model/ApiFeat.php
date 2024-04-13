<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model;

class ApiFeat
{
    public function __construct(
        public readonly ?int $componentTypeId,
        public readonly ?int $componentId,
        public readonly ApiFeatDefinition $definition,
        public readonly int $definitionId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['componentTypeId'],
            $data['componentId'],
            ApiFeatDefinition::fromApi($data['definition']),
            $data['definitionId'],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $data
     *
     * @return array<int, self>
     */
    public static function createCollectionFromApi(array $data): array
    {
        $featCollection = [];

        foreach ($data as $feat) {
            $featCollection[] = self::fromApi($feat);
        }

        return $featCollection;
    }
}
