<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/src',
        __DIR__.'/tests',
        __DIR__.'/tools',
    ]);

return (new PhpCsFixer\Config())
    ->setRules([
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'declare_strict_types' => false,
        'modern_serialization_methods' => false,
        'no_php4_constructor' => false,
        'no_trailing_whitespace_in_string' => true,
        'php_unit_construct' => false,
        'php_unit_mock_short_will_return' => false,
        'php_unit_set_up_tear_down_visibility' => false,
        'php_unit_test_annotation' => false,
        'protected_to_private' => false,
        'static_lambda' => false,
        'void_return' => false,
    ])
    ->setRiskyAllowed(true)
    ->setFinder($finder);
