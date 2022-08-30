<?php

namespace loyen\DndbCharacterSheet\Model;

class Character implements \JsonSerializable
{
    private string $name;
    private CharacterArmorClass $armorClass;
    /**
     * @var array<string, CharacterAbility> $abilityScores
     */
    private array $abilityScores;
    private int $proficiencyBonus;
    private int $level;
    /**
     * @var array<int, CharacterClass> $classes
     */
    private array $classes;
    /**
     * @var array<string, int> $currencies
     */
    private array $currencies;
    private CharacterHealth $health;
    /**
     * @var array<string, CharacterMovement> $movementSpeeds
     */
    private array $movementSpeeds;
    /**
     * @var array<string, array<int, string>> $proficiencies
     */
    private array $proficiencies;
    /**
     * @var array<int, Item> $inventory
     */
    private array $inventory;

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param array<string, CharacterAbility> $abilityScores
     */
    public function setAbilityScores(array $abilityScores): void
    {
        $this->abilityScores = $abilityScores;
    }

    /**
     * @return array<string, CharacterAbility>
     */
    public function getAbilityScores(): array
    {
        return $this->abilityScores;
    }

    public function setArmorClass(CharacterArmorClass $armorClass): void
    {
        $this->armorClass = $armorClass;
    }

    public function getArmorClass(): CharacterArmorClass
    {
        return $this->armorClass;
    }


    /**
     * @param array<int, CharacterClass> $classes
     */
    public function setClasses(array $classes): void
    {
        $this->classes = $classes;
    }

    /**
     * @param array<int, Item> $inventory
     */
    public function setInventory(array $inventory): void
    {
        $this->inventory = $inventory;
    }

    /**
     * @return array<int, Item>
     */
    public function getInventory(): array
    {
        return $this->inventory;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    /**
     * @return array<int, CharacterClass>
     */
    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * @param array<string, int> $currencies
     */
    public function setCurrencies(array $currencies): void
    {
        $this->currencies = $currencies;
    }

    /**
     * @return array<string, int>
     */
    public function getCurrencies(): array
    {
        return $this->currencies;
    }

    public function getHealth(): CharacterHealth
    {
        return $this->health;
    }

    public function setHealth(CharacterHealth $health): void
    {
        $this->health = $health;
    }

    public function setProficiencyBonus(int $proficiencyBonus): void
    {
        $this->proficiencyBonus = $proficiencyBonus;
    }

    public function getProficiencyBonus(): int
    {
        return $this->proficiencyBonus;
    }

    /**
     * @param array<string, CharacterMovement> $movementSpeeds
     */
    public function setMovementSpeeds(array $movementSpeeds): void
    {
        $this->movementSpeeds = $movementSpeeds;
    }

    /**
     * @return array<string, CharacterMovement>
     */
    public function getMovementSpeeds(): array
    {
        return $this->movementSpeeds;
    }

    /**
     * @param array<string, array<int, string>> $proficiencies
     */
    public function setProficiencies(array $proficiencies): void
    {
        $this->proficiencies = $proficiencies;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getProficiencies(): array
    {
        return $this->proficiencies;
    }

    public function jsonSerialize(): mixed
    {
        return \get_object_vars($this);
    }
}
