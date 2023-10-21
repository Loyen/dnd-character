<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        '@PER-CS' => true,
        '@PER-CS:risky' => true,
        'array_syntax' => ['syntax' => 'short'],
        'nullable_type_declaration_for_default_null_value' => ['use_nullable_type_declaration' => true],
        'yoda_style' => false,
    ])
    ->setFinder($finder);
