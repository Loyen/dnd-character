<?php

namespace loyen\DndbCharacterLight\Character\Model;

class CharacterStat implements \JsonSerializable
{
    public function __construct(
        public readonly CharacterStatTypes $type,
        public readonly int $value = 0,
        public readonly array $modifiers = []
    )
    {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->type->name(),
            'value' => $this->value + array_sum($this->modifiers)
        ];
    }
}
