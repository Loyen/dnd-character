<?php

namespace loyen\DndbCharacterSheet\Character\Model;

class CharacterAbility implements \JsonSerializable
{
    public function __construct(
        public readonly CharacterAbilityTypes $type,
        public readonly int $value = 0,
        public readonly array $modifiers = [],
        public readonly ?int $overrideValue = null,
        public readonly ?bool $savingThrowProficient = false,
    )
    {
    }

    public function jsonSerialize(): mixed
    {
        $modifierTotal = array_sum($this->modifiers);
        $abilityScore = $this->overrideValue ?? $this->value + $modifierTotal;

        $calulatedModifier = round(($abilityScore - 10)/2);

        return [
            'name' => $this->type->name(),
            'value' => $abilityScore,
            'modifier' => $calulatedModifier,
            'savingThrowProficient' => $this->savingThrowProficient
        ];
    }
}
