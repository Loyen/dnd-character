<?php

namespace DndSheet\Model;

class CharacterProficiency implements \JsonSerializable
{
    public function __construct(
        public readonly ProficiencyGroup $type,
        public readonly string $name,
        public readonly ProficiencyType $proficiencyLevel = ProficiencyType::Proficient,
    ) {}

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->name,
            'proficiencyLevel' => $this->proficiencyLevel->value,
        ];
    }
}
