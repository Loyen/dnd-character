<?php

namespace loyen\DndbCharacterSheet\Importer\CustomYaml\Model;

class YamlMovement
{
    public function __construct(
        public readonly int $walk,
        public readonly int $fly,
        public readonly int $burrow,
        public readonly int $swim,
        public readonly int $climb,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromData(array $data): self
    {
        return new self(
            $data['walk'] ?? 0,
            $data['fly'] ?? 0,
            $data['burrow'] ?? 0,
            $data['swim'] ?? 0,
            $data['climb'] ?? 0
        );
    }
}
