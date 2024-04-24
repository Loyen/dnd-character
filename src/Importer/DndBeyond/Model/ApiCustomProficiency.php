<?php

namespace DndCharacter\Importer\DndBeyond\Model;

class ApiCustomProficiency
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $type,
        public readonly ?int $statId,
        public readonly int $proficiencyLevel,
        public readonly ?string $notes,
        public readonly mixed $override,
        public readonly mixed $magicBonus,
        public readonly mixed $miscBonus,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['type'],
            $data['statId'],
            $data['proficiencyLevel'],
            $data['notes'],
            $data['override'],
            $data['magicBonus'],
            $data['miscBonus'],
        );
    }

    /**
     * @param array<int, array<string, int|string|null>> $data
     *
     * @return array<int, self>
     */
    public static function createCollectionFromApi(array $data): array
    {
        $statCollection = [];

        foreach ($data as $stat) {
            $statCollection[] = self::fromApi($stat);
        }

        return $statCollection;
    }
}
