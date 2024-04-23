<?php

namespace DndSheet\Importer;

use DndSheet\Model\Character;

interface ImporterInterface
{
    public static function import(string $inputString): Character;
}
