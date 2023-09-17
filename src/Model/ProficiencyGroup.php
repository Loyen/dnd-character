<?php

namespace loyen\DndbCharacterSheet\Model;

enum ProficiencyGroup: int
{
    case Ability = 1958004211;
    case Armor = 174869515;
    case Language = 906033267;
    case Tool = 2103445194;
    case Weapon = 1782728300;
    case WeaponGroup = 660121713;

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
