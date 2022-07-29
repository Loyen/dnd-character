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
        public readonly array $modifiers = []
    )
    {
    }

    public function jsonSerialize(): mixed
    {
        $modifierTotal = array_sum($this->modifiers);
        $abilityScore = $this->value + $modifierTotal;

        return [
            'name' => $this->type->name(),
            'value' => $abilityScore
        ];
    }
}
