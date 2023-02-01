<?php

namespace loyen\DndbCharacterSheet\Model;

use loyen\DndbCharacterSheet\Model\DnDBeyond\ApiBookSource;

class SourceMaterial implements \JsonSerializable
{
    public function __construct(
        public readonly Source $source,
        public readonly ?int $pageNumber
    ) {
    }

    /**
     * @param array<int, ApiBookSource> $sources
     * @return array<int, self>
     */
    public static function createCollection(array $sources): array
    {
        /** @var array<int, self> */
        $sourceList = [];

        foreach ($sources as $source) {
            $sourceType = Source::tryFrom($source->sourceId) ?? Source::UnknownSource;

            $sourceList[] = new self(
                $sourceType,
                $source->pageNumber
            );
        }

        return $sourceList;
    }

    public function jsonSerialize(): mixed
    {
        return $this->source !== Source::UnknownSource
            ? $this->source->name() . ', pg ' . $this->pageNumber
            : $this->source->name();
    }
}
