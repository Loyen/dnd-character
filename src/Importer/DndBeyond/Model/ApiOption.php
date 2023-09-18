<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model;

class ApiOption
{
    public function __construct(
        public readonly int $componentId,
        public readonly int $componentTypeId,
        public readonly ApiOptionDefinition $definition
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['componentId'],
            $data['componentTypeId'],
            ApiOptionDefinition::fromApi($data['definition'])
        );
    }

    /**
     * @param array<int, array<string, mixed>> $data
     *
     * @return array<int, self>
     */
    public static function createCollectionFromApi(array $data): array
    {
        $optionCollection = [];

        foreach ($data as $option) {
            $optionCollection[] = self::fromApi($option);
        }

        return $optionCollection;
    }

    /**
     * @param array<string, array<int, array<string, mixed>>|null> $data
     *
     * @return array<string, array<int, self>>
     */
    public static function createCollectionPerCategoryFromApi(array $data): array
    {
        $optionCollectionPerCategory = [];

        foreach ($data as $categoryName => $optionList) {
            if ($optionList === null) {
                continue;
            }

            $optionCollectionPerCategory[$categoryName] = self::createCollectionFromApi($optionList);
        }

        return $optionCollectionPerCategory;
    }
}
