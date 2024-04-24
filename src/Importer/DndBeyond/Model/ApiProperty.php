<?php

namespace DndCharacter\Importer\DndBeyond\Model;

class ApiProperty
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $description,
        public readonly ?string $notes,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['id'],
            $data['name'],
            $data['description'],
            $data['notes'],
        );
    }

    /**
     * @param array<int, array<string, int|string|null>> $data
     *
     * @return array<int, self>
     */
    public static function createCollectionFromApi(array $data): array
    {
        $propertyCollection = [];

        foreach ($data as $property) {
            $propertyCollection[] = self::fromApi($property);
        }

        return $propertyCollection;
    }
}
