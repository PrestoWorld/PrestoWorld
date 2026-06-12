<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/framework/presto',
        __DIR__ . '/framework/witals/src',
        __DIR__ . '/tests',
    ])
    ->notPath([
        'framework/presto/Modules/Gutenberg/Pattern/wp-stubs.php',
    ]);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PHP82Migration' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline' => ['elements' => ['arrays', 'arguments', 'parameters']],
        'binary_operator_spaces' => [
            'default' => 'single_space',
            'operators' => ['=>' => null],
        ],
        'blank_line_after_namespace' => true,
        'blank_line_after_opening_tag' => true,
        'single_quote' => true,
        'no_extra_blank_lines' => true,
        'return_type_declaration' => ['space_before' => 'none'],
        'declare_strict_types' => true,
    ])
    ->setFinder($finder);
