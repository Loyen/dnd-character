<?php

namespace DndCharacter\Importer\DndBeyond\Model;

class ApiModifier
{
    public function __construct(
        public readonly ?int $fixedValue,
        public readonly int $id,
        public readonly ?int $entityId,
        public readonly ?int $entityTypeId,
        public readonly string $type,
        public readonly string $subType,
        public readonly ?ApiDice $dice,
        public readonly ?string $restriction,
        public readonly ?int $statId,
        public readonly bool $requiresAttunement,
        /** @var array<string, mixed>|null */
        public readonly ?array $duration,
        public readonly string $friendlyTypeName,
        public readonly string $friendlySubtypeName,
        public readonly bool $isGranted,
        /** @var array<int, mixed> */
        public readonly array $bonusTypes,
        public readonly ?int $value,
        public readonly bool $availableToMulticlass,
        public readonly int $modifierTypeId,
        public readonly int $modifierSubTypeId,
        public readonly int $componentId,
        public readonly int $componentTypeId,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    private static function fromApi(array $data): self
    {
        return new self(
            $data['fixedValue'],
            $data['id'],
            $data['entityId'],
            $data['entityTypeId'],
            $data['type'],
            $data['subType'],
            $data['dice'] !== null ? ApiDice::fromApi($data['dice']) : null,
            $data['restriction'],
            $data['statId'],
            $data['requiresAttunement'],
            $data['duration'],
            $data['friendlyTypeName'],
            $data['friendlySubtypeName'],
            $data['isGranted'],
            $data['bonusTypes'],
            $data['value'],
            $data['availableToMulticlass'],
            $data['modifierTypeId'],
            $data['modifierSubTypeId'],
            $data['componentId'],
            $data['componentTypeId'],
        );
    }

    /**
     * @param array<int, array<string, array<string, mixed>>> $data
     *
     * @return array<string|int, array<int, self>>
     */
    public static function createCollectionFromApi(array $data): array
    {
        $modifierCollection = [];

        foreach ($data as $modifierType => $modifiers) {
            $modifierCollection[$modifierType] = [];

            foreach ($modifiers as $modifier) {
                $modifierCollection[$modifierType][] = self::fromApi($modifier);
            }
        }

        return $modifierCollection;
    }
}
