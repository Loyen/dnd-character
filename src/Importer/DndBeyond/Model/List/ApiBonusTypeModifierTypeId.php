<?php

namespace DndCharacter\Importer\DndBeyond\Model\List;

enum ApiBonusTypeModifierTypeId: int
{
    case Bonus = 1;
    case Set = 9;
    case StackingBonus = 38;
}
