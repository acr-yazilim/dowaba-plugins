<?php
/**
 * PHP-CS-Fixer config — PrestaShop validator (validator.prestashop.com) compliance.
 *
 * Rules of interest (per validator Standards tab):
 * - concat_space: spacing 'one'  → 'foo'.'bar' must become 'foo' . 'bar'
 * - nullable_type_declaration_for_default_null_value → use_nullable_type_declaration: false
 *   PrestaShop core BC convention (PHP 7.4 implicit nullable preferred):
 *   ?string $x = null  →  string $x = null
 */

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__.'/src')
    ->name('*.php');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        'concat_space' => ['spacing' => 'one'],
        'nullable_type_declaration_for_default_null_value' => ['use_nullable_type_declaration' => false],
        'single_quote' => true,
        'no_blank_lines_after_phpdoc' => true,
        'binary_operator_spaces' => true,
        'trailing_comma_in_multiline' => true,
        'no_useless_return' => true,
        'no_useless_else' => true,
        'phpdoc_no_alias_tag' => false,
    ])
    ->setFinder($finder);
