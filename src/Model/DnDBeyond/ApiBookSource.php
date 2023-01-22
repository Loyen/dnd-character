<?php

namespace loyen\DndbCharacterSheet\Model\DnDBeyond;

class ApiBookSource
{
    public function __construct(
        public readonly int $sourceId,
        public readonly ?int $pageNumber,
        public readonly int $sourceType
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['sourceId'],
            $data['pageNumber'],
            $data['sourceType']
        );
    }

    /**
     * @param array<int, array<string, int|null>> $data
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
