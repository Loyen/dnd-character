<?php

namespace loyen\DndbCharacterSheet\Model;

class CharacterTraits implements \JsonSerializable
{
    public function __construct(
        /** @var string[] */
        public readonly array $traits,
    ) {}

    /**
     * @return string[]
     */
    public function jsonSerialize(): array
    {
        return $this->traits;
    }
}
