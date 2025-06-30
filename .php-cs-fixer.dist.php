<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in([
        __DIR__ . '/app',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/routes',
        __DIR__ . '/tests',
    ])
    ->name('*.php')
    ->ignoreDotFiles(true)
    ->ignoreVCS(true);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12'                          => true,
        'array_syntax'                    => ['syntax' => 'short'],
        'trim_array_spaces'               => true,
        'whitespace_after_comma_in_array' => true,
        'array_indentation'               => true,
        'binary_operator_spaces'          => [
            'default'   => 'align_single_space_minimal',
            'operators' => ['=>' => 'align_single_space_minimal'],
        ],
        'blank_line_before_statement' => [
            'statements' => ['return'],
        ],
        'ordered_imports'                   => ['sort_algorithm' => 'alpha'],
        'no_unused_imports'                 => true,
        'single_quote'                      => true,
        'not_operator_with_successor_space' => true,
        'trailing_comma_in_multiline'       => ['elements' => ['arrays']],
        'phpdoc_scalar'                     => true,
        'phpdoc_align'                      => ['align' => 'left'],
    ])
    ->setFinder($finder);
