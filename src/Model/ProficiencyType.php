<?php

namespace loyen\DndbCharacterSheet\Model;

enum ProficiencyType: int
{
    case ABILITY = 1958004211;
    case ARMOR = 174869515;
    case LANGUAGE = 906033267;
    case TOOL = 2103445194;
    case WEAPON = 1782728300;
    case WEAPONGROUP = 660121713;

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
