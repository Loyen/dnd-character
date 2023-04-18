<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model;

class ApiClass
{
    public function __construct(
        public readonly int $id,
        public readonly int $entityTypeId,
        public readonly int $level,
        public readonly bool $isStartingClass,
        public readonly int $hitDiceUsed,
        public readonly int $definitionId,
        public readonly ?int $subclassDefinitionId,
        public readonly ApiClassDefinition $definition,
        public readonly ?ApiClassDefinition $subclassDefinition,
        /** @var array<int, array<string, mixed>> */
        public readonly array $classFeatures
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['id'],
            $data['entityTypeId'],
            $data['level'],
            $data['isStartingClass'],
            $data['hitDiceUsed'],
            $data['definitionId'],
            $data['subclassDefinitionId'],
            ApiClassDefinition::fromApi($data['definition']),
            $data['subclassDefinition'] !== null
                ? ApiClassDefinition::fromApi($data['subclassDefinition'])
                : null,
            $data['classFeatures']
        );
    }

    /**
     * @param array<int, array<string, mixed>> $data
     *
     * @return array<int, self>
     */
    public static function createCollectionFromApi(array $data): array
    {
        $classCollection = [];

        foreach ($data as $class) {
            $classCollection[] = self::fromApi($class);
        }

        return $classCollection;
    }
}
