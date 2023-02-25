<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model;

class ApiChoice
{
    public function __construct(
        public readonly int $componentId,
        public readonly int $componentTypeId,
        public readonly string $id,
        public readonly ?string $parentChoiceId,
        public readonly int $type,
        public readonly ?int $subType,
        public readonly ?int $optionValue,
        public readonly ?string $label,
        public readonly bool $isOptional,
        public readonly bool $isInfinite,
        /** @var array<int, string> */
        public readonly array $defaultSubtypes,
        public readonly ?int $displayOrder,
        /** @var array<int, mixed> */
        public readonly array $options,
        /** @var array<int, int> */
        public readonly array $optionIds
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['componentId'],
            $data['componentTypeId'],
            $data['id'],
            $data['parentChoiceId'],
            $data['type'],
            $data['subType'],
            $data['optionValue'],
            $data['label'],
            $data['isOptional'],
            $data['isInfinite'],
            $data['defaultSubtypes'],
            $data['displayOrder'],
            $data['options'],
            $data['optionIds']
        );
    }

    /**
     * @param array<int, array<string, mixed>> $data
     * @return array<int, self>
     */
    public static function createCollectionFromApi(array $data): array
    {
        $choiceCollection = [];

        foreach ($data as $choice) {
            $choiceCollection[] = self::fromApi($choice);
        }

        return $choiceCollection;
    }
   /**
    * @param array<string, array<int, array<string, mixed>>|null> $data
    * @return array<string, array<int, self>>
    */
    public static function createCollectionPerCategoryFromApi(array $data): array
    {
        $choiceCollectionPerCategory = [];

        foreach ($data as $categoryName => $choiceList) {
            if ($choiceList === null || $categoryName === 'choiceDefinitions') {
                continue;
            }

            $choiceCollectionPerCategory[$categoryName] = self::createCollectionFromApi($choiceList);
        }

        return $choiceCollectionPerCategory;
    }
}
