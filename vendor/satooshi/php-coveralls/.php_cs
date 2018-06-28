<?php

return Symfony\CS\Config\Config::create()
    ->setUsingCache(true)
    // use default SYMFONY_LEVEL and change the set with fixers:
    ->fixers([
        '-concat_without_spaces',
        '-phpdoc_no_package',
        '-unalign_double_arrow',
        '-unalign_equals',
        'concat_with_spaces',
        'php_unit_construct',
        'php_unit_strict',
        'strict',
        'strict_param',
    ])
    ->finder(
        Symfony\CS\Finder\DefaultFinder::create()
            ->in(__DIR__ . '/tests/Satooshi')
            ->in(__DIR__ . '/src')
    )
;
