<?php

namespace loyen\DndbCharacterSheet\Model;

enum BonusType: int
{
    case BONUS = 1;
    case SET = 9;
    case STACKING_BONUS = 38;
}
