<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model;

class ApiStat
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $name,
        public readonly int $value
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            \is_int($data['value']) ? $data['value'] : 0
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
