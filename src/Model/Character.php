<?php

namespace DndSheet\Model;

class Character implements \JsonSerializable
{
    private string $name;
    private CharacterArmorClass $armorClass;
    /**
     * @var array<string, CharacterAbility>
     */
    private array $abilityScores;
    private int $proficiencyBonus;
    private int $level;
    /**
     * @var array<int, CharacterClass>
     */
    private array $classes;
    /**
     * @var array<string, int>
     */
    private array $currencies;
    private CharacterHealth $health;
    /**
     * @var array<int, CharacterFeature>
     */
    private array $features;
    /**
     * @var array<string, CharacterMovement>
     */
    private array $movementSpeeds;
    /**
     * @var array<string, array<int, CharacterProficiency>>
     */
    private array $proficiencies;
    /**
     * @var array<int, Item>
     */
    private array $inventory;
    private CharacterTraits $traits;

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
     * @return array<int, CharacterClass>
     */
    public function getClasses(): array
    {
        return $this->classes;
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

    /**
     * @param array<int, CharacterFeature> $features
     */
    public function setFeatures(array $features): void
    {
        $this->features = $features;
    }

    /**
     * @return array<int, CharacterFeature>
     */
    public function getFeatures(): array
    {
        $features = array_merge(
            $this->features,
            ...array_map(
                fn($c) => $c->getFeatures(),
                $this->classes,
            ),
        );

        sort($features);

        return $features;
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
     * @param array<string, array<int, CharacterProficiency>> $proficiencies
     */
    public function setProficiencies(array $proficiencies): void
    {
        $this->proficiencies = $proficiencies;
    }

    /**
     * @return array<string, array<int, CharacterProficiency>>
     */
    public function getProficiencies(): array
    {
        return $this->proficiencies;
    }

    public function setTraits(CharacterTraits $traits): void
    {
        $this->traits = $traits;
    }

    public function getTraits(): CharacterTraits
    {
        return $this->traits;
    }

    public function jsonSerialize(): mixed
    {
        return get_object_vars($this);
    }
}
