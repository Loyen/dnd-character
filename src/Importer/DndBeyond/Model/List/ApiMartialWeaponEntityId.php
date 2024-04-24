<?php

namespace DndCharacter\Importer\DndBeyond\Model\List;

enum ApiMartialWeaponEntityId: int
{
    case Battleaxe = 19;
    case Flail = 20;
    case Glaive = 2;
    case Greataxe = 21;
    case Greatsword = 22;
    case Halberd = 23;
    case Lance = 24;
    case Longsword = 4;
    case Maul = 25;
    case Morningstar = 26;
    case Pike = 27;
    case Rapier = 28;
    case Scimitar = 29;
    case Shortsword = 30;
    case Trident = 31;
    case WarPick = 32;
    case Warhammer = 33;
    case Whip = 34;

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
