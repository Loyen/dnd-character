<?php

namespace loyen\DndbCharacterLight\Character\Model;

class Character implements \JsonSerializable
{
    public function __construct(
        public readonly string $name,
        public readonly array $stats,
    )
    {
    }

    public function jsonSerialize(): mixed
    {
        return \get_object_vars($this);
    }
}
