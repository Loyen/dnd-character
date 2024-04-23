<?php

namespace DndSheet\Importer\DndBeyond\Model\List;

enum ApiCustomProficiencyType: int
{
    case Language = 3;

    /**
     * @return array<int, int>
     */
    public static function getValues(): array
    {
        return array_column(
            self::cases(),
            'value',
        );
    }
}
