<?php

namespace loyen\DndbCharacterSheet\Model;

enum MovementType
{
    case WALK;
    case FLY;
    case BURROW;
    case SWIM;
    case CLIMB;

    public function name(): string {
        return match($this) {
            self::WALK => 'Walking',
            self::FLY => 'Flying',
            self::BURROW => 'Burrowing',
            self::SWIM => 'Swimming',
            self::CLIMB => 'Climbing'
        };
    }
}
