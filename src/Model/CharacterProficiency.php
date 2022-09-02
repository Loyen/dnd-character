<?php

namespace loyen\DndbCharacterSheet\Model;

class CharacterProficiency implements \JsonSerializable
{
    public function __construct(
        public readonly ProficiencyType $type,
        public readonly string $name,
        public readonly bool $expertise = false,
    ) {

    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'expertise' => $this->expertise
        ];
    }
}
