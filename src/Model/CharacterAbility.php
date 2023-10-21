<?php

namespace loyen\DndbCharacterSheet\Model;

class CharacterAbility implements \JsonSerializable
{
    private AbilityType $type;
    private int $value = 0;
    /**
     * @var array<int, int>
     */
    private array $modifiers = [];
    private ?int $overrideValue = null;
    private ?bool $savingThrowProficient = false;

    public function __construct(AbilityType $type)
    {
        $this->type = $type;
    }

    public function setType(AbilityType $type): void
    {
        $this->type = $type;
    }

    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    /**
     * @param array<int, int> $modifiers
     */
    public function setModifiers(array $modifiers): void
    {
        $this->modifiers = $modifiers;
    }

    public function setOverrideValue(?int $overrideValue): void
    {
        $this->overrideValue = $overrideValue;
    }

    public function setSavingThrowProficient(?bool $savingThrowProficient): void
    {
        $this->savingThrowProficient = $savingThrowProficient;
    }

    public function getType(): AbilityType
    {
        return $this->type;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * @return array<int, int>
     */
    public function getModifiers(): array
    {
        return $this->modifiers;
    }

    public function getOverrideValue(): ?int
    {
        return $this->overrideValue;
    }

    public function isSavingThrowProficient(): ?bool
    {
        return $this->savingThrowProficient;
    }

    public function getCalculatedValue(): int
    {
        return min(30, $this->overrideValue)
            ?? min(20, $this->value + array_sum($this->modifiers));
    }

    public function getCalculatedModifier(): int
    {
        return (int) floor(($this->getCalculatedValue() - 10) / 2);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->type->name(),
            'value' => $this->getCalculatedValue(),
            'modifier' => $this->getCalculatedModifier(),
            'savingThrowProficient' => $this->savingThrowProficient,
        ];
    }
}
