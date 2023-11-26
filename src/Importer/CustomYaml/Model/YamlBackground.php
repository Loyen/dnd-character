<?php

namespace loyen\DndbCharacterSheet\Importer\CustomYaml\Model;

class YamlBackground
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $description,
        /** @var YamlFeature[] */
        public readonly array $features,
        /** @var YamlSource[] */
        public readonly array $sources
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromData(array $data): self
    {
        return new self(
            $data['name'],
            $data['description'] ?? null,
            isset($data['features'])
                ? YamlFeature::createCollectionFromData($data['features'])
                : [],
            isset($data['sources'])
                ? YamlSource::createCollectionFromData($data['sources'])
                : []
        );
    }
}
