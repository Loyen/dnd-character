<?php

namespace loyen\DndbCharacterSheet\Model;

enum ProficiencyType: int
{
    case ABILITY = 1958004211;
    case ARMOR = 174869515;
    case WEAPON = 1782728300;
    case WEAPONGROUP = 660121713;

    public static function getValues(): array
    {
        return \array_column(
            self::cases(),
            'value'
        );
    }
}
