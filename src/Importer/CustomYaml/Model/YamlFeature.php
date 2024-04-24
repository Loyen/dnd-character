<?php

namespace DndCharacter\Importer\CustomYaml\Model;

class YamlFeature
{
    public function __construct(
        public ?string $name = null,
        public int $level = 1,
        public string $description = '',
        /** @var YamlSource[] */
        public array $sources = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromData(array $data): self
    {
        return match ($data['type'] ?? null) {
            YamlFeatureType::AbilityScoreImprovements->value => YamlFeatureAbilityScoreImprovement::fromData($data),
            YamlFeatureType::MovementImprovement->value => YamlFeatureMovementImprovement::fromData($data),
            YamlFeatureType::ProficiencyImprovement->value => YamlFeatureProficiencyImprovement::fromData($data),
            default => new self(
                $data['name'],
                $data['level'] ?? 0,
                $data['description'] ?? '',
                isset($data['sources'])
                        ? YamlSource::createCollectionFromData($data['sources'])
                        : [],
            ),
        };
    }

    /**
     * @param array<int, array<string, int|null>> $data
     *
     * @return self[]
     */
    public static function createCollectionFromData(array $data): array
    {
        $featureCollection = [];

        foreach ($data as $feature) {
            $featureCollection[] = self::fromData($feature);
        }

        return $featureCollection;
    }
}
