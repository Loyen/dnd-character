<?php

namespace DndSheet\Importer\CustomYaml\Model;

enum YamlAbilityScore: string
{
    case Strength = 'Strength';
    case Dexterity = 'Dexterity';
    case Constitution = 'Constitution';
    case Intelligence = 'Intelligence';
    case Wisdom = 'Wisdom';
    case Charisma = 'Charisma';
}
