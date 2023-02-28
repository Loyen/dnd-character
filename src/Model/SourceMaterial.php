<?php

namespace loyen\DndbCharacterSheet\Model;

use loyen\DndbCharacterSheet\Importer\DndBeyond\Model\ApiBookSource;

class SourceMaterial implements \JsonSerializable
{
    public function __construct(
        public readonly string $title,
        public readonly ?int $pageNumber
    ) {
    }

    public function jsonSerialize(): mixed
    {
        return $this->pageNumber !== null
            ? $this->title . ', pg ' . $this->pageNumber
            : $this->title;
    }
}
