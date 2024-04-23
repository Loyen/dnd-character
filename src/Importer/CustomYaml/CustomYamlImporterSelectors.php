<?php

namespace DndSheet\Importer\CustomYaml;

enum CustomYamlImporterSelectors: string
{
    case AbilityScoreImprovements = 'Ability Score Improvement';
    case MovementImprovement = 'Movement Improvement';
    case ProficiencyImprovement = 'Proficiency Improvement';
    case ProficiencyArmors = 'armors';
    case ProficiencyLanguages = 'languages';
    case ProficiencySavingThrows = 'savingThrows';
    case ProficiencySkills = 'skills';
    case ProficiencyTools = 'tools';
    case ProficiencyWeapons = 'weapons';
}
