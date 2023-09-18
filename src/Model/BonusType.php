<?php

namespace loyen\DndbCharacterSheet\Model;

enum BonusType: int
{
    case Bonus = 1;
    case Set = 9;
    case StackingBonus = 38;
}
