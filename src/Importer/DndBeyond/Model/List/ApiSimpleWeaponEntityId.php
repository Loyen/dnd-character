<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model\List;

enum ApiSimpleWeaponEntityId: int
{
    case Club = 5;
    case Dagger = 3;
    case Greatclub = 6;
    case Handaxe = 7;
    case Javelin = 8;
    case LightHammer = 10;
    case Mace = 11;
    case Quarterstaff = 12;
    case Sickle = 13;
    case Spear = 14;

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
