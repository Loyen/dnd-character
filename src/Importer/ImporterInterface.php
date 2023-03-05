<?php

namespace loyen\DndbCharacterSheet\Importer;

use loyen\DndbCharacterSheet\Model\Character;

interface ImporterInterface
{
    public static function import(string $inputString): Character;
}
