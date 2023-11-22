<?php

namespace loyen\DndbCharacterSheet\Importer\CustomYaml\Model;

use loyen\DndbCharacterSheet\Model\SourceMaterial;

class YamlFeatureMovementImprovement extends YamlFeature
{
    public function __construct(
        public ?string $name,
        public int $level,
        public string $description,
        public YamlMovement $movement,
        /** @var SourceMaterial[] */
        public array $sources = []
    ) {}

    public static function fromData(array $data): self
    {
        return new self(
            $data['name'] ?? YamlFeatureType::MovementImprovement->value,
            $data['level'] ?? 0,
            $data['description'] ?? '',
            YamlMovement::fromData($data['movement']),
            isset($data['sources'])
                    ? YamlSource::createCollectionFromData($data['sources'])
                    : [],
        );
    }
}
