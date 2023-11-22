<?php

namespace loyen\DndbCharacterSheet\Importer\CustomYaml\Model;

class YamlSource
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $extra
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromData(array $data): self
    {
        return new self(
            $data['name'],
            $data['extra'],
        );
    }

    /**
     * @param array<int, array<string, int|null>> $data
     *
     * @return array<int, self>
     */
    public static function createCollectionFromData(array $data): array
    {
        $sourceCollection = [];

        foreach ($data as $source) {
            $sourceCollection[] = self::fromData($source);
        }

        return $sourceCollection;
    }
}
