<?php

namespace loyen\DndbCharacterSheet\Model\DnDBeyond;

class ApiRace
{
    public function __construct(
        /** @var array<string, array<string, int>>  */
        public readonly array $weightSpeeds
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['weightSpeeds']
        );
    }
}
