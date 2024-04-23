<?php

namespace DndSheet\Model;

class SourceMaterial implements \JsonSerializable
{
    public function __construct(
        public readonly string $title,
        public readonly ?string $extra = null,
    ) {}

    public function jsonSerialize(): mixed
    {
        return $this->extra !== null
            ? $this->title . ', ' . $this->extra
            : $this->title;
    }
}
