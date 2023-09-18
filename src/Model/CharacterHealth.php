<?php

namespace loyen\DndbCharacterSheet\Model;

class CharacterHealth implements \JsonSerializable
{
    /**
     * @param array<int, int> $modifiers
     */
    public function __construct(
        public readonly int $value = 0,
        public readonly array $modifiers = [],
        public readonly int $temporaryHitPoints = 0,
        public readonly ?int $overrideValue = null,
    ) {}

    public function getMaxHitPoints(): int
    {
        return $this->overrideValue ?? $this->value;
    }

    public function getCurrentHitPoints(): int
    {
        return (int) ($this->getMaxHitPoints() + \array_sum($this->modifiers));
    }

    public function jsonSerialize(): mixed
    {
        return [
            'value' => $this->getCurrentHitPoints(),
            'max' => $this->getMaxHitPoints(),
            'temporary' => $this->temporaryHitPoints,
        ];
    }
}
