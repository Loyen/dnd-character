<?php

namespace DndSheet\Importer\DndBeyond\Model;

class ApiLevelScale
{
    public function __construct(
        public readonly int $id,
        public readonly int $level,
        public readonly string $description,
        public readonly ApiDice $dice,
        public readonly ?int $fixedValue,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['id'],
            $data['level'],
            $data['description'],
            ApiDice::fromApi($data['dice']),
            $data['fixedValue'],
        );
    }

    /**
     * @param array<int, array<string, mixed>> $data
     *
     * @return array<int, self>
     */
    public static function createCollectionFromApi(array $data): array
    {
        $featureCollection = [];

        foreach ($data as $feature) {
            $featureCollection[] = self::fromApi($feature);
        }

        return $featureCollection;
    }
}
