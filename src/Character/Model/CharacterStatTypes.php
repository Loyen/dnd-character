<?php

namespace loyen\DndbCharacterLight\Character\Model;

enum CharacterStatTypes: int
{
    case STR = 1;
    case DEX = 2;
    case CON = 3;
    case INT = 4;
    case WIS = 5;
    case CHA = 6;

    public function name(): string {
        return match($this) {
            self::STR => "Strength",
            self::DEX => "Dexterity",
            self::CON => "Consitution",
            self::INT => "Intelligence",
            self::WIS => "Wisdom",
            self::CHA => "Charisma",
        };
    }
}
