<?php

namespace loyen\DndbCharacterSheet\Model;

use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiBookSource;

class SourceMaterial implements \JsonSerializable
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $extra = null
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return $this->extra !== null
            ? $this->title . ', ' . $this->extra
            : $this->title;
    }
}
