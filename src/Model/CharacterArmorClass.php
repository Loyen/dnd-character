<?php

namespace DndCharacter\Model;

class CharacterArmorClass implements \JsonSerializable
{
    /**
     * @var array<int, CharacterAbility>
     */
    private array $abilityScores = [];
    private int $value = 10;
    private ?int $overrideValue = null;
    private ?Item $armor = null;
    /**
     * @var array<int, int>
     */
    private array $modifiers = [];

    public function setArmor(Item $armor): void
    {
        $this->armor = $armor;
    }

    public function addAbilityScore(CharacterAbility $abilityScore): void
    {
        $this->abilityScores[] = $abilityScore;
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

    /**
     * @return array<int, CharacterAbility>
     */
    public function getAbilityScores(): array
    {
        return $this->abilityScores;
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

    public function isWearingArmor(): bool
    {
        return $this->armor !== null;
    }

    public function getCalculatedValue(): int
    {
        if ($this->overrideValue) {
            return $this->overrideValue;
        }

        $abilityScoreModifier = 0;
        foreach ($this->abilityScores as $ability) {
            $abilityScoreModifier += $ability->getCalculatedModifier();
        }

        if ($this->armor !== null) {
            $value = $this->armor->getArmorClass();

            if ($this->armor->getArmorType() === ArmorType::MediumArmor) {
                $abilityScoreModifier = min(2, $abilityScoreModifier);
            } elseif ($this->armor->getArmorType() === ArmorType::HeavyArmor) {
                $abilityScoreModifier = 0;
            }
        } else {
            $value = $this->value;
        }

        return (int) ($value + $abilityScoreModifier + array_sum($this->modifiers));
    }

    public function jsonSerialize(): mixed
    {
        return $this->getCalculatedValue();
    }
}
