<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model;

class ApiDice
{
    public function __construct(
        public readonly ?int $diceCount,
        public readonly ?int $diceValue,
        public readonly ?int $diceMultiplier,
        public readonly ?int $fixedValue,
        public readonly ?string $diceString
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['diceCount'],
            $data['diceValue'],
            $data['diceMultiplier'],
            $data['fixedValue'],
            $data['diceString']
        );
    }
}
