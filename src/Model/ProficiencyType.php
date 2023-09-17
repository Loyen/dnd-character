<?php

namespace loyen\DndbCharacterSheet\Model;

enum ProficiencyType: string
{
    case NotProficient = 'none';
    case HalfProficient = 'half-proficient';
    case Proficient = 'proficient';
    case Expertise = 'expertise';
}
