<?php

namespace loyen\DndbCharacterSheet\Model;

class CharacterFeature implements \JsonSerializable
{
    public function __construct(
        public readonly string $name
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name
        ];
    }
}
