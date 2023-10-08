<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model\List;

enum ApiMartialRangedWeaponEntityId: int
{
    case Blowgun = 35;
    case CrossbowHand = 1;
    case CrossbowHeavy = 36;
    case Longbow = 37;
    case Net = 38;

    /**
     * @return array<int, int>
     */
    public static function getValues(): array
    {
        return \array_column(
            self::cases(),
            'value'
        );
    }
}
