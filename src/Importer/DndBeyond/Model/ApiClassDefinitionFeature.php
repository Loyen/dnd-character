<?php

namespace DndCharacter\Importer\DndBeyond\Model;

class ApiClassDefinitionFeature
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly mixed $prerequisite,
        public readonly string $description,
        public readonly int $requiredLevel,
        public readonly int $displayOrder,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['prerequisite'],
            $data['description'],
            $data['requiredLevel'],
            $data['displayOrder'],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $data
     *
     * @return array<int, self>
     */
    public static function createCollectionFromApi(array $data): array
    {
        $featureCollection = [];

        foreach ($data as $feature) {
            $featureCollection[] = self::fromApi($feature);
        }

        return $featureCollection;
    }
}
