<?php

namespace loyen\DndbCharacterSheet\Model;

class CharacterClass implements \JsonSerializable
{
    private int $level = 1;
    private string $name;
    private ?string $subName = null;
    private array $features = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function setLevel(int $level): void
    {
        $this->level = $level;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setSubName(string $subName): void
    {
        $this->subName = $subName;
    }

    public function setFeatures(array $features): void
    {
        $this->features = $features;
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSubName(): ?string
    {
        return $this->subName;
    }

    public function getFeatures(): array
    {
        return $this->features;
    }

    public function addFeature(string $feature): void
    {
        $this->features[] = $feature;
    }

    public function jsonSerialize(): mixed
    {
        return \get_object_vars($this);
    }
}
