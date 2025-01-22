<?php

declare(strict_types=1);

$finder = Symfony\Component\Finder\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->notName('*.cache.php')
    ->notPath('Tempest/Router/src/Route.php') // phpcs doesn't yet support property hooks in interfaces
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setCacheFile('.cache/fixer/cs-fixer.cache')
    ->setRules([
        '@PSR12' => true,
        '@PHP84Migration' => true,
        'ordered_attributes' => true,
        'ordered_traits' => true,
        'attribute_empty_parentheses' => true,
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
            'less_and_greater' => false
        ],
        'get_class_to_class_keyword' => true,
        'cast_spaces' => true,
        'single_space_around_construct' => true,
        'heredoc_indentation' => true,
        'types_spaces' => true,
        'single_quote' => true,
        'no_short_bool_cast' => true,
        'explicit_string_variable' => true,
        'no_extra_blank_lines' => [
            'tokens' => [
                'case',
                'continue',
                'curly_brace_block',
                'default',
                'extra',
                'parenthesis_brace_block',
                'square_brace_block',
                'switch',
                'throw',
                'use',
            ],
        ],
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => [
            'sort_algorithm' => 'alpha',
            'imports_order' => [
                'class',
                'function',
                'const',
            ],
        ],
        'no_unused_imports' => true,
        'no_unneeded_import_alias' => true,
        'blank_line_between_import_groups' => false,
        'single_import_per_statement' => true,
        'no_leading_import_slash' => true,
        'fully_qualified_strict_types' => [
            'import_symbols' => true,
            'phpdoc_tags' => [],
        ],
        'global_namespace_import' => [
            'import_classes' => true,
            'import_constants' => true,
            'import_functions' => true,
        ],
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline' => [
            'elements' => ['arrays', 'arguments', 'parameters', 'match'],
        ],
        'phpdoc_scalar' => true,
        'unary_operator_spaces' => true,
        'binary_operator_spaces' => true,
        'blank_line_before_statement' => [
            'statements' => ['break', 'continue', 'declare', 'return', 'throw', 'try'],
        ],
        'phpdoc_single_line_var_spacing' => true,
        'phpdoc_var_without_name' => true,
        'class_attributes_separation' => [
            'elements' => [
                'method' => 'one',
            ],
        ],
        'method_argument_space' => [
            'on_multiline' => 'ensure_fully_multiline',
            'keep_multiple_spaces_after_comma' => true,
        ],
        'type_declaration_spaces' => true,
        'single_trait_insert_per_statement' => true,
        'declare_strict_types' => true,
        'no_empty_comment' => true,
        'no_empty_phpdoc' => true,

        // Test styling
        'php_unit_data_provider_name' => [
            'prefix' => 'provide_',
            'suffix' => '_cases',
        ],
        'php_unit_data_provider_return_type' => true,
        'php_unit_data_provider_static' => [
            'force' => true,
        ],
        'php_unit_dedicate_assert_internal_type' => true,
        'php_unit_internal_class' => true,
        'php_unit_method_casing' => [
            'case' => 'snake_case',
        ],
        'php_unit_expectation' => [
            'target' => 'newest',
        ],
        'php_unit_mock' => [
            'target' => 'newest',
        ],
        'php_unit_mock_short_will_return' => true,
        'php_unit_set_up_tear_down_visibility' => true,
        'visibility_required' => [
            'elements' => [
//            'property' // Disabled because of PHP 8.4 aviz
                'method',
                'const'
            ],
        ],
        'php_unit_size_class' => false,
        'php_unit_test_annotation' => [
            'style' => 'prefix',
        ],
        'php_unit_test_case_static_method_calls' => [
            'call_type' => 'this',
        ],
    ])
    ->setFinder($finder);
