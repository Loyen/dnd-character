<?php

namespace DndSheet\Importer\DndBeyond\Model\List;

enum ApiModifierTypeModifierTypeId: int
{
    case HalfProficiency = 13;
    case HalfProficiencyRoundUp = 29;
    case Expertise = 12;
    case Proficiency = 10;
}
