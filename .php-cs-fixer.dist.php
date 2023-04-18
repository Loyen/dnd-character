<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PER' => true,
        '@Symfony' => true,
        'yoda_style' => false,
        'concat_space' => [ 'spacing' => 'one' ],
        'array_syntax' => [ 'syntax' => 'short' ]
    ])
    ->setFinder($finder);
