<?php

namespace DndCharacter\Importer\DndBeyond\Model\List;

use DndCharacter\Model\ProficiencyGroup;

enum ApiProficiencyGroupEntityTypeId: int
{
    case Ability = 1958004211;
    case Armor = 174869515;
    case Language = 906033267;
    case Tool = 2103445194;
    case Weapon = 1782728300;
    case WeaponGroup = 660121713;

    public function toProficiencyGroup(): ProficiencyGroup
    {
        return match ($this) {
            self::Ability => ProficiencyGroup::Ability,
            self::Armor => ProficiencyGroup::Armor,
            self::Language => ProficiencyGroup::Language,
            self::Tool => ProficiencyGroup::Tool,
            self::Weapon => ProficiencyGroup::Weapon,
            self::WeaponGroup => ProficiencyGroup::WeaponGroup,
        };
    }
}
