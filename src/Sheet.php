<?php

namespace DndCharacter;

use DndCharacter\Model\Character;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Sheet
{
    private Environment $twig;

    public function __construct(
        ?Environment $twig = null,
    ) {
        $this->twig = $twig ?? new Environment(
            new FilesystemLoader(\dirname(__DIR__) . '/template'),
        );
    }

    public function render(
        Character $character,
        string $template = 'light-sheet.twig.html',
    ): string {
        return $this->twig->load($template)->render([
            'character' => $character,
        ]);
    }
}
