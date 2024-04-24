<?php

namespace DndCharacter\Importer;

use DndCharacter\Model\Character;

interface ImporterInterface
{
    public static function import(string $inputString): Character;
}
