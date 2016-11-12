<?php

namespace PhpCsFixer;

return Config::create()
    ->setRules([
        '@PSR1'    => true,
        '@PSR2'    => true,
        '@Symfony' => true,
        'combine_consecutive_unsets'                => true,
        'concat_with_spaces'                        => true,
        'concat_without_spaces'                     => false,
        'dir_constant'                              => true,
        'linebreak_after_opening_tag'               => true,
        'modernize_types_casting'                   => true,
        'no_multiline_whitespace_before_semicolons' => true,
        'no_php4_constructor'                       => true,
        'no_useless_else'                           => true,
        'no_useless_return'                         => true,
        'ordered_class_elements'                    => true,
        'ordered_imports'                           => true,
        'phpdoc_no_package'                         => false,
        'phpdoc_order'                              => true,
        'pre_increment'                             => false,
        'psr0'                                      => true,
        'psr4'                                      => true,
        'random_api_migration'                      => true,
        'semicolon_after_instruction'               => true,
        'short_array_syntax'                        => true,
        'simplified_null_return'                    => true,
        'unalign_double_arrow'                      => false,
        'unalign_equals'                            => false,
    ])->setFinder(
        Finder::create()
            ->in(__DIR__.'/src')
    );
