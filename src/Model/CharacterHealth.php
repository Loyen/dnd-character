<?php

namespace loyen\DndbCharacterSheet\Model;

class CharacterHealth implements \JsonSerializable
{
    /**
     * @param array<int, mixed> $modifiers
     */
    public function __construct(
        public readonly int $value = 0,
        public readonly array $modifiers = [],
        public readonly int $temporaryHitPoints = 0,
        public readonly ?int $overrideValue = null,
    )
    {
    }

    public function jsonSerialize(): mixed
    {
        $maxHitPoints = $this->overrideValue ?? $this->value;
        $currentHitPoints = $maxHitPoints + array_sum($this->modifiers);

        return [
            'value' => $currentHitPoints,
            'max' => $maxHitPoints,
            'temporary' => $this->temporaryHitPoints
        ];
    }
}
