<?php

namespace DndSheet\Importer\CustomYaml\Model;

use DndSheet\Importer\CustomYaml\Exception\CharacterYamlDataException;

class YamlFeatureProficiencyImprovement extends YamlFeature
{
    public function __construct(
        public ?string $name,
        public int $level,
        public string $description,
        public YamlProficiencyCategory $category,
        /** @var string[] */
        public array $proficiencies = [],
        /** @var YamlSource[] */
        public array $sources = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromData(array $data): self
    {
        return new self(
            $data['name'] ?? YamlFeatureType::ProficiencyImprovement->value,
            $data['level'] ?? 0,
            $data['description'] ?? '',
            YamlProficiencyCategory::tryFrom($data['category'])
                ?? throw new CharacterYamlDataException('Missing category for feature'),
            $data['proficiencies'],
            isset($data['sources'])
                    ? YamlSource::createCollectionFromData($data['sources'])
                    : [],
        );
    }
}
