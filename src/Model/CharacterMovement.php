<?php

namespace loyen\DndbCharacterSheet\Model;

class CharacterMovement implements \JsonSerializable
{
    /**
     * @param array<int, int> $modifiers
     */
    public function __construct(
        public readonly MovementType $type,
        public readonly int $value = 0,
        public readonly array $modifiers = [],
    ) {}

    public function getCalculatedValue(): int
    {
        return (int) ($this->value + array_sum($this->modifiers));
    }

    public function jsonSerialize(): mixed
    {
        return $this->getCalculatedValue();
    }
}
