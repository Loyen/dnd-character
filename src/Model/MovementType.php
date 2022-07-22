<?php

namespace loyen\DndbCharacterSheet\Model;

enum MovementType: string
{
    case WALK = 'walk';
    case FLY = 'fly';
    case BURROW = 'burrow';
    case SWIM = 'swim';
    case CLIMB = 'climb';

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
