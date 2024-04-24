<?php

namespace DndCharacter\Importer\CustomYaml\Model;

enum YamlFeatureType: string
{
    case AbilityScoreImprovements = 'Ability Score Improvement';
    case MovementImprovement = 'Movement Improvement';
    case ProficiencyImprovement = 'Proficiency Improvement';
}
