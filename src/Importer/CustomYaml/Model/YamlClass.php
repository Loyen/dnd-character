<?php

namespace loyen\DndbCharacterSheet\Importer\CustomYaml\Model;

class YamlClass
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $subclass,
        public readonly int $level,
        /** @var YamlSource[] */
        public readonly array $sources,
        /** @var array<string, int> */
        public readonly array $hitPoints,
        /** @var YamlFeature[] */
        public readonly array $features
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromData(array $data): ?self
    {
        return new self(
            $data['name'],
            $data['subclass'] ?? null,
            $data['level'] ?? 0,
            isset($data['sources'])
                ? YamlSource::createCollectionFromData($data['sources'])
                : [],
            $data['hitPoints'] ?? [],
            isset($data['features'])
                ? YamlFeature::createCollectionFromData($data['features'])
                : []
        );
    }

    /**
     * @param array<int, array<string, int|null>> $data
     *
     * @return array<int, self>
     */
    public static function createCollectionFromData(array $data): array
    {
        $classCollection = [];

        foreach ($data as $class) {
            $classCollection[] = self::fromData($class);
        }

        return $classCollection;
    }
}
