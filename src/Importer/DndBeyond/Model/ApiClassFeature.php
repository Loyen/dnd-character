<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model;

class ApiClassFeature
{
    public function __construct(
        public readonly ApiClassFeatureDefinition $definition
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            ApiClassFeatureDefinition::fromApi($data['definition'])
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
