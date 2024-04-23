<?php

namespace DndSheet\Importer\DndBeyond\Model;

class ApiTraits
{
    public function __construct(
        public readonly string $personalityTraits,
        public readonly string $ideals,
        public readonly string $bonds,
        public readonly string $flaws,
        public readonly string $appearance,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromApi(array $data): self
    {
        return new self(
            $data['personalityTraits'] ?? '',
            $data['ideals'] ?? '',
            $data['bonds'] ?? '',
            $data['flaws'] ?? '',
            $data['appearance'] ?? '',
        );
    }
}
