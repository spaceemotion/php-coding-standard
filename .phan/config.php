<?php

return [
    'exclude_analysis_directory_list' => [
        'vendor/',
    ],
    'enable_include_path_checks' => true,
    'plugins' => [
        'AlwaysReturnPlugin',
        'DollarDollarPlugin',
        'DuplicateArrayKeyPlugin',
        'DuplicateExpressionPlugin',
        'PregRegexCheckerPlugin',
        'PrintfCheckerPlugin',
        'SleepCheckerPlugin',
        'UnreachableCodePlugin',
        'UseReturnValuePlugin',
        'EmptyStatementListPlugin',
        'LoopVariableReusePlugin',
    ],
    'directory_list' => [
        'src',
        'vendor'
    ],
    'file_list' => [
        'bin/phpcstd',
    ],
];
