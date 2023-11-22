<?php

namespace loyen\DndbCharacterSheet\Importer\CustomYaml\Model;

class YamlFeatureMovementImprovement extends YamlFeature
{
    public function __construct(
        public ?string $name,
        public int $level,
        public string $description,
        public YamlMovement $movement,
        /** @var YamlSource[] */
        public array $sources = []
    ) {}

    /** @param array<string, mixed> $data */
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
