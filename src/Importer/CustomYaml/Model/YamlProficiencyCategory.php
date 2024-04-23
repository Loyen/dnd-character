<?php

namespace DndSheet\Importer\CustomYaml\Model;

enum YamlProficiencyCategory: string
{
    case Armors = 'Armors';
    case Languages = 'Languages';
    case Skills = 'Skills';
    case SavingThrows = 'Saving Throws';
    case Tools = 'Tools';
    case Weapons = 'Weapons';
}
