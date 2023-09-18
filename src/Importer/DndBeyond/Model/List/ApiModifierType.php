<?php

namespace loyen\DndbCharacterSheet\Importer\DndBeyond\Model\List;

enum ApiModifierType: int
{
    case HalfProficiency = 13;
    case HalfProficiencyRoundUp = 29;
    case Expertise = 12;
    case Proficiency = 10;
}
