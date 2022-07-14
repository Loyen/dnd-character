<?php

namespace loyen\DndbCharacterSheet\Character\Model;

class CharacterMovement implements \JsonSerializable
{
    public function __construct(
        public readonly CharacterMovementTypes $type,
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
            'value' => $abilityScore,
            'modifier' => $modifierTotal
        ];
    }
}
