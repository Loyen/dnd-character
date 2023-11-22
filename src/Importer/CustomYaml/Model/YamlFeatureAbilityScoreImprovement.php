<?php

namespace loyen\DndbCharacterSheet\Importer\CustomYaml\Model;

use loyen\DndbCharacterSheet\Model\SourceMaterial;

class YamlFeatureAbilityScoreImprovement extends YamlFeature
{
    public function __construct(
        public ?string $name = null,
        public int $level = 1,
        public string $description = '',
        /** @var YamlAbilityScore[] */
        public array $abilities = [],
        /** @var SourceMaterial[] */
        public array $sources = []
    ) {}

    public static function fromData(array $data): self
    {
        return new self(
            $data['name'] ?? YamlFeatureType::AbilityScoreImprovements->value,
            $data['level'] ?? 0,
            $data['description'] ?? '',
            $data['abilities'],
            isset($data['sources'])
                    ? YamlSource::createCollectionFromData($data['sources'])
                    : [],
        );
    }
}
