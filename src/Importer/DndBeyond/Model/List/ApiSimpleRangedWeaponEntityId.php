<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model\List;

enum ApiSimpleRangedWeaponEntityId: int
{
    case CrossbowLight = 15;
    case Dart = 16;
    case Shortbow = 17;
    case Sling = 18;

    /**
     * @return array<int, int>
     */
    public static function getValues(): array
    {
        return array_column(
            self::cases(),
            'value'
        );
    }
}
