<?php

namespace loyen\DndbCharacterSheet\Model;

class CharacterArmorClass implements \JsonSerializable
{
    private ?CharacterAbility $dexterityAbility;
    private int $value = 10;
    private ?int $overrideValue = null;
    private ?Item $armor = null;
    /**
     * @var array<int, int> $modifiers
     */
    private array $modifiers = [];

    public function setArmor(Item $armor): void
    {
        $this->armor = $armor;
    }

    public function setDexterityAbility(CharacterAbility $dexterityAbility): void
    {
        $this->dexterityAbility = $dexterityAbility;
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

    public function setValue(int $value): void
    {
        $this->value = $value;
    }

    public function getArmor(): ?Item
    {
        return $this->armor;
    }

    public function getDexterityAbility(): ?CharacterAbility
    {
        return $this->dexterityAbility;
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

    public function getValue(): int
    {
        return $this->value;
    }

    public function getCalculatedValue(): int
    {
        if ($this->overrideValue) {
            return $this->overrideValue;
        }

        $value = $this->armor?->getArmorClass() ?? $this->value;

        $dexterityModifier = max(0, $this->dexterityAbility?->getCalculatedModifier());
        if ($this->armor?->getArmorTypeId() === 2) {
            $dexterityModifier = min(2, $dexterityModifier);
        }

        return $value + $dexterityModifier + array_sum($this->modifiers);
    }

    public function jsonSerialize(): mixed
    {

        return $this->getCalculatedValue();
    }
}
