<?php

namespace loyen\DndbCharacterSheet\Model\DnDBeyond;

class ApiOptionDefinition
{
    public function __construct(
        public readonly int $id,
        public readonly int $entityTypeId,
        public readonly string $name,
        public readonly string $description,
        public readonly string $snippet,
        /** @var array<string, int>|null */
        public readonly ?array $activation,
        public readonly ?int $sourceId,
        public readonly ?int $sourcePageNumber,
        /** @var array<int, mixed> */
        public readonly array $creatureRules,
        /** @var array<int, mixed> */
        public readonly array $spellListIds
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
            $data['name'],
            $data['description'],
            $data['snippet'],
            $data['activation'],
            $data['sourceId'],
            $data['sourcePageNumber'],
            $data['creatureRules'],
            $data['spellListIds']
        );
    }
}
