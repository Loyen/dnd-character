<?php

namespace DndCharacter\Model;

enum AbilityType: string
{
    case STR = 'Strength';
    case DEX = 'Dexterity';
    case CON = 'Constitution';
    case INT = 'Intelligence';
    case WIS = 'Wisdom';
    case CHA = 'Charisma';
}
