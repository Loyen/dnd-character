<?php

namespace loyen\DndbCharacterLight\Character\Model;

class CharacterStat implements \JsonSerializable
{
    public function __construct(
        public readonly CharacterStatTypes $type,
        public readonly int $value = 0,
        public readonly array $modifiers = [],
        public readonly ?int $overrideValue = null
    )
    {
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->type->name(),
            'value' => $this->overrideValue ?? ($this->value + array_sum($this->modifiers))
        ];
    }
}
