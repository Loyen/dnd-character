<?php

namespace loyen\DndbCharacterSheet\Importer\CustomYaml\Model;

class YamlCharacter
{
    public function __construct(
        public readonly string $name,
        /** @var array<string, int> */
        public readonly array $abilityScores,
        public readonly YamlRace $race,
        /** @var YamlClass[] */
        public readonly array $classes,
        public readonly YamlBackground $background,
        public readonly mixed $inventory,
        public readonly mixed $wallet,
    ) {}

    public static function fromYaml(string $yaml): ?self
    {
        $data = yaml_parse($yaml);

        if (empty($data)) {
            return null;
        }

        return new self(
            $data['name'],
            $data['abilityScores'],
            YamlRace::fromData($data['race']),
            isset($data['classes'])
                ? YamlClass::createCollectionFromData($data['classes'])
                : [],
            YamlBackground::fromData($data['background']),
            $data['inventory'] ?? [],
            $data['wallet'] ?? [],
        );
    }
}
