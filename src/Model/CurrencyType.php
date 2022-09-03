<?php

namespace loyen\DndbCharacterSheet\Model;

enum CurrencyType: string
{
    case CP = 'cp';
    case SP = 'sp';
    case GP = 'gp';
    case EP = 'ep';
    case PP = 'pp';

    public function name(): string
    {
        return match ($this) {
            self::CP => 'Copper',
            self::SP => 'Silver',
            self::GP => 'Gold',
            self::EP => 'Electrum',
            self::PP => 'Platinum',
        };
    }
}
