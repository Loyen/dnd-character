<?php

namespace loyen\DndbCharacterSheet\Importer\CustomYaml\Model;

class YamlRace
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $subrace,
        /** @var YamlSource[] */
        public readonly array $sources,
        public readonly string $size,
        public readonly YamlMovement $movement,
        /** @var YamlFeature[] */
        public readonly array $features,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromData(array $data): self
    {
        return new self(
            $data['name'],
            $data['subrace'] ?? null,
            isset($data['sources'])
                ? YamlSource::createCollectionFromData($data['sources'])
                : [],
            $data['size'],
            YamlMovement::fromData($data['movement']),
            isset($data['features'])
                ? YamlFeature::createCollectionFromData($data['features'])
                : [],
        );
    }
}
