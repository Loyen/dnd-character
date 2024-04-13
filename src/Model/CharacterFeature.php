<?php

namespace loyen\DndbCharacterSheet\Model;

class CharacterFeature implements \JsonSerializable
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        /** @var array<int, SourceMaterial> */
        public readonly array $sources,
    ) {}

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'sources' => $this->sources,
        ];
    }
}
