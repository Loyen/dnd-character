<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model;

enum ModifierType: int
{
    case HalfProficiency = 13;
    case HalfProficiencyRoundUp = 29;
    case Expertise = 12;
    case Proficiency = 10;
}
