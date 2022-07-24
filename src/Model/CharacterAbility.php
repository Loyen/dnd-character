<?php

namespace loyen\DndbCharacterSheet\Model;

class CharacterAbility implements \JsonSerializable
{
    public function __construct(
        public readonly AbilityType $type,
        public readonly int $value = 0,
        public readonly array $modifiers = [],
        public readonly ?int $overrideValue = null,
        public readonly ?bool $savingThrowProficient = false,
    )
    {
    }

    public function getCalculatedValue(): int
    {
        return $this->overrideValue
            ?? $this->value + array_sum($this->modifiers);
    }

    public function getCalculatedModifier(): float
    {
        return floor(($this->getCalculatedValue() - 10)/2);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->type->name(),
            'value' => $this->getCalculatedValue(),
            'modifier' => $this->getCalculatedModifier(),
            'savingThrowProficient' => $this->savingThrowProficient
        ];
    }
}
